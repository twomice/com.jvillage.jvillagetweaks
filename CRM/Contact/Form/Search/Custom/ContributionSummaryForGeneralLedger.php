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

class CRM_Contact_Form_Search_Custom_ContributionSummaryForGeneralLedger extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  protected $_formValues;

  function __construct(&$formValues) {
      $this->_formValues = $formValues;
      $summarize_by =  $this->_formValues['summarize_by'];

      if ($summarize_by == 'daily' || $summarize_by == 'daily,pi' ||  $summarize_by == 'daily_alone' ) {
        $date_label = 'Receive Date (yyyy-mm-dd)';
      }
      else if($summarize_by == 'weekly' || $summarize_by == 'weekly,pi' ||  $summarize_by == 'weekly_alone' ) {
        $date_label = 'Week begining (yyyy-mm-dd)';
      }
      else if($summarize_by == 'monthly' || $summarize_by == 'monthly,pi' ||  $summarize_by == 'monthly_alone') {
        $date_label = 'Month (yyyy-mm)';
      }
      else if($summarize_by == 'details') {
        $date_label =  'Contrib. Date';
      }

      /**
       * Define the columns for search result rows
       */

      $this->_columns = array(
        ts($date_label) => 'date',
        ts('Currency') => 'currency',
        ts('Amount') => 'total_amount',
        ts('Financial Type') => 'contribution_type_name',
        ts('Financial Type Description') => 'financial_description',
        ts('Accouting Code') => 'accounting_code',
        ts('Financial Set') => 'financial_category',
        ts('Payment Instrument') => 'pay_type_label',
        ts('Project Name') => 'campaign_title',
        ts('Project Code') => 'campaign_external_id',
        ts('Legacy Deposit ID') => 'deposit_id',
        ts('Legacy Batch ID') => 'batch_id',
        ts('Num. Records Combined') => 'rec_count',
      );

      if($summarize_by == 'details')   {
        $this->_columns['Contact'] = 'sort_name';
        $this->_columns['Contrib. ID'] = 'contrib_id';
        $this->_columns['Contrib. Source'] = 'contrib_source';
        $this->_columns['Contrib. Check Number'] = 'check_number';
        $this->_columns['Contrib. Transaction ID'] = 'contrib_transaction_id';
        $this->_columns['Contrib. Status'] = 'contrib_status_name' ;
        $this->_columns['Line Item ID'] = 'line_item_id';

        // contr.source as contrib_source, contr.check_number as check_number, contr.trxn_id as contrib_transaction_id
      }
  }

  function buildForm(&$form) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Contribution Summary');

        /**
         * Define the search form fields here
         */
       require_once 'utils/util_money.php';
       if ( pogstone_is_user_authorized('access CiviContribute') == false ){
         $this->setTitle('Not Authorized');
         return;
       }

      // $tmp =  $form->add ('textarea', 'intro', 'Overview');
      // $tmp->setValue( "This is ");

        $form->addDate('start_date', ts('Contribution Date From'), false, array( 'formatType' => 'custom' ) );
        $form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );

        // Batch ID range
         $form->add('text', 'start_batch_id', ts('Legacy Batch ID from'));
         $form->add('text', 'end_batch_id', ts('...Legacy Batch ID through'));

       // Deposit ID range
        $form->add('text', 'start_deposit_id', ts('Legacy Deposit ID from'));
        $form->add('text', 'end_deposit_id', ts('...Legacy Deposit ID through'));

        $summary_array = array();
        $summary_array['daily'] = "Day, Financial Type, Project, Payment Instrument";
        $summary_array['daily,pi'] = "Day, Payment Instrument";
        $summary_array['daily_alone'] = "Day";
        $summary_array['weekly'] = "Week, Financial Type, Project, Payment Instrument";
        $summary_array['weekly,pi'] = "Week, Payment Instrument ";
        $summary_array['weekly_alone'] = "Week";
        $summary_array['monthly'] = "Month, Financial Type, Project, Payment Instrument";
        $summary_array['monthly,pi'] = "Month, Payment Instrument";
        $summary_array['monthly_alone'] = "Month";
        $summary_array['deposit'] = "Legacy Deposit ID, Financial Type, Project, Payment Instrument";
        $summary_array['deposit,pi'] = "Legacy Deposit ID, Payment Instrument";
        $summary_array['batch'] = "Legacy Batch ID, Financial Type, Project, Payment Instrument";
        $summary_array['batch,pi'] = "Legacy Batch ID, Payment Instrument";
        $summary_array['details'] = "All Details - No Summary";

        $form->addElement('select', 'summarize_by', ts('Summarize By'),  $summary_array);

        require_once( 'utils/finance/FinancialCategory.php') ;
        $tmpFinCategory = new FinancialCategory();

       // $tmpFinCatArray = array( ''   => ' - select financial categories - ') ;
       $tmpFinCatArray = array( ) ;

        $tmpFinCategory->getCategoryList($tmpFinCatArray);

       /*
         $tmp_select = $form->addElement('select', 'financial_category', ts('Financial Category'), $tmpFinCatArray);
         $tmp_select->setMultiple(true);
       */

        $form->add( 'select', 'financial_category', ts('Financial Sets'), $tmpFinCatArray, FALSE,
          array('id' => 'financial_category', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );

     //   $contrib_type_choices = array( ''   => ' - select contribution types - ' );
     //   $accounting_code_choices = array( ''   => ' - select accounting codes - ' );

        $contrib_type_choices = array();
	$accounting_code_choices = array();

        require_once('utils/finance/Prepayment.php');
        $tmpPrepayment = new Prepayment();
        //$tmp_exlude_prepayment_sql = $tmpPrepayment->getExcludePrepaymentsSQL();

	$contrib_type_sql = "Select ct.id, ct.name, fa.accounting_code as accounting_code from civicrm_financial_type ct
               LEFT JOIN civicrm_entity_financial_account far
                 ON ct.id = far.entity_id AND far.entity_table = 'civicrm_financial_type' AND far.account_relationship = 1
               LEFT JOIN civicrm_financial_account fa ON far.financial_account_id = fa.id
               WHERE ct.is_active = 1 ".$tmp_exlude_prepayment_sql." order by ct.name";

        $contrib_dao = CRM_Core_DAO::executeQuery($contrib_type_sql);

         while ($contrib_dao->fetch()) {
              $cur_id = $contrib_dao->id;
              $cur_name = $contrib_dao->name;
              $accounting_code = $contrib_dao->accounting_code;

              $pos_a = strpos($cur_name, 'adjustment-');
             // $pos_b = strpos($cur_name, 'prepayment-');

           if ($pos_a === false ) {
                if (strlen($accounting_code) > 0) {
                    $tmp_description = $cur_name." - ".$accounting_code;
                    $accounting_code_choices[$accounting_code] = $accounting_code;
                }
                else {
                  $tmp_description = $cur_name;
                }

                $contrib_type_choices[$cur_id] = $tmp_description;
           }
         }

        $contrib_dao->free();

       natcasesort ($accounting_code_choices);

       $contrib_type_sql = "";

       $form->add('select', 'contrib_type', ts('Financial Types'), $contrib_type_choices, FALSE,
          array('id' => 'contrib_type', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );


  $form->add('select', 'accounting_code', ts('Accounting Codes'),  $accounting_code_choices, FALSE,
          array('id' => 'accounting_code', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );

         // Get various payment instruments
         $payment_instrument_choices = array();
         $results = civicrm_api("OptionGroup","getsingle", array (version => '3','sequential' =>'1', 'name' =>'payment_instrument'));

         $pi_group_id = $results['id'];

         $results_pi_tmp =  civicrm_api("OptionValue","get", array (version => '3','sequential' =>'1', 'option_group_id' => $pi_group_id));

         $tmp_pi = $results_pi_tmp['values'];

         foreach($tmp_pi as $cur){
           $payment_instrument_choices[$cur['value']] = $cur['label'];
         }

         // Should have all the payment instruments in the array $payment_instrument_choices at this point.
         $form->add('select', 'payment_instruments', ts('Payment Instruments'),  $payment_instrument_choices, FALSE,
          array('id' => 'payment_instruments', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );

      /**
       * If you are using the sample template, this array tells the template fields to render
       * for the search form.
       */
      $form->assign( 'elements', array(  'start_date', 'end_date', 'start_batch_id', 'end_batch_id', 'start_deposit_id', 'end_deposit_id',   'payment_instruments' , 'contrib_type', 'accounting_code',  'financial_category', 'summarize_by' ) );
  }

  /**
   * Define the smarty template used to layout the search form and results listings.
   */
  function templateFile() {
    return 'CRM/Contact/Form/Search/ContributionSummary.tpl';
  }

  function prepare_sql_string($includeContactIDs = false, $onlyIDs = false) {
    // check authority of end-user
    require_once 'utils/util_money.php';
    if ( pogstone_is_user_authorized('access CiviContribute') == false ){
      return "select 'You are not authorized to this area' as total_amount from  civicrm_contact where 1=0 limit 1";
    }

    // If summary_only:   $select = "currency, sum(line_total) as total_amount, count(*) as rec_count";

    // SELECT clause must include contact_id as an alias for civicrm_contact.id
    if ($onlyIDs) {
      // [ML] redmine:481 This makes no sense? Custom searches expect the contact_id.
      // Also, was missing the $refund_select.
      // $select  = "contr.receive_date";

      $select = 'c.id as contact_id';
      $refund_select = 'c.id as contact_id';
    }
    else {
      $custom_field_group_label = "Extra Contribution Info";
      $custom_field_deposit_label = "Deposit id";
      $custom_field_batch_label = "Batch id" ;

      $customFieldLabels = array($custom_field_deposit_label   , $custom_field_batch_label );
      $extended_contrib_table = "";
      $outCustomColumnNames = array();

      require_once('utils/util_custom_fields.php');
      getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_contrib_table, $outCustomColumnNames ) ;

      $deposit_sql_name  =  $outCustomColumnNames[$custom_field_deposit_label];
      $batch_sql_name  =  $outCustomColumnNames[$custom_field_batch_label];

      require_once ('utils/finance/FinancialCategory.php');
      $tmpFinCategory = new FinancialCategory();

      $financial_category_field_sql = $tmpFinCategory->getFinancialCategoryFieldAsSQL();

      $acct_code = " fa.accounting_code ";

      $summarize_by = $this->_formValues['summarize_by'];

      if ($summarize_by == 'daily') {
        $tmp_select_date = "ov.label as pay_type_label,  date(contr.receive_date) as date, ".$financial_category_field_sql."
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
        ct.name as contribution_type_name, ".$acct_code." , ct.description as financial_description,  '' as deposit_id, '' as batch_id, ";
          $tmp_refund_select_date = "ov.label as pay_type_label,  date(contr.cancel_date) as date, ".$financial_category_field_sql."
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
        ct.name as contribution_type_name, ".$acct_code." , ct.description as financial_description,  '' as deposit_id, '' as batch_id, ";

        $tmp_groupby_date = "GROUP BY contr.currency, date(contr.receive_date), ct.name, contr.campaign_id, contr.payment_instrument_id";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, date(contr.cancel_date), ct.name, contr.campaign_id, contr.payment_instrument_id";

      }
      else if ($summarize_by == 'weekly') {
        $tmp_select_date = "ov.label as pay_type_label,  date(DATE_ADD(contr.receive_date, INTERVAL(1-DAYOFWEEK(contr.receive_date)) DAY)) as date, ".$financial_category_field_sql."
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
        ct.name as contribution_type_name, ".$acct_code." , ct.description as financial_description, '' as deposit_id, '' as batch_id,
                 ";
       $tmp_refund_select_date =  "ov.label as pay_type_label,  date(DATE_ADD(contr.cancel_date, INTERVAL(1-DAYOFWEEK(contr.cancel_date)) DAY)) as date, ".$financial_category_field_sql."
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
        ct.name as contribution_type_name, ".$acct_code." , ct.description as financial_description, '' as deposit_id, '' as batch_id,
                 ";
        $tmp_groupby_date = "GROUP BY contr.currency, concat(year(contr.receive_date), week(contr.receive_date, 0 )), ct.name, contr.campaign_id, contr.payment_instrument_id ";
        $tmp_refund_groupby_date =  "GROUP BY contr.currency, concat(year(contr.cancel_date), week(contr.cancel_date, 0 )), ct.name, contr.campaign_id, contr.payment_instrument_id ";

      }
      else if ($summarize_by == 'monthly') {
        $tmp_select_date = "ov.label as pay_type_label,  concat(year(contr.receive_date) , '-', month(contr.receive_date) ) as date, ".$financial_category_field_sql."
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
        ct.name as contribution_type_name, ".$acct_code." , ct.description as financial_description, '' as deposit_id, '' as batch_id, ";
         $tmp_refund_select_date = "ov.label as pay_type_label,  concat(year(contr.cancel_date) , '-', month(contr.cancel_date) ) as date, ".$financial_category_field_sql."
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
        ct.name as contribution_type_name, ".$acct_code." , ct.description as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_groupby_date = "GROUP BY contr.currency, concat(year(contr.receive_date), month(contr.receive_date)), ct.name, contr.campaign_id, contr.payment_instrument_id ";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, concat(year(contr.cancel_date), month(contr.cancel_date)), ct.name, contr.campaign_id, contr.payment_instrument_id ";
      }
      else if($summarize_by == 'deposit') {
        $tmp_select_date = "ov.label as pay_type_label,  concat(min(contr.receive_date) , '-', max(contr.receive_date) ) as date,
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
         ct.name as contribution_type_name, ".$acct_code." , ".$financial_category_field_sql." eci.".$deposit_sql_name." as deposit_id, '' as batch_id,  ";
            $tmp_refund_select_date = "ov.label as pay_type_label,  concat(min(contr.cancel_date) , '-', max(contr.cancel_date) ) as date,
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
         ct.name as contribution_type_name, ".$acct_code." , ".$financial_category_field_sql." eci.".$deposit_sql_name." as deposit_id, '' as batch_id,  ";
        $tmp_groupby_date = "GROUP BY contr.currency, eci.".$deposit_sql_name.",  ct.name, contr.campaign_id,  contr.payment_instrument_id ";
          $tmp_refund_groupby_date = "GROUP BY contr.currency, eci.".$deposit_sql_name.",  ct.name, contr.campaign_id,  contr.payment_instrument_id ";

      }else if($summarize_by == 'batch'){
        $tmp_select_date = "ov.label as pay_type_label,  concat(min(contr.receive_date) , '-', max(contr.receive_date) ) as date,
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
         ct.name as contribution_type_name, ".$acct_code." , ".$financial_category_field_sql."  '' as deposit_id, eci.".$batch_sql_name." as batch_id, ";

         $tmp_refund_select_date = "ov.label as pay_type_label,  concat(min(contr.cancel_date) , '-', max(contr.cancel_date) ) as date,
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
         ct.name as contribution_type_name, ".$acct_code." , ".$financial_category_field_sql."  '' as deposit_id, eci.".$batch_sql_name." as batch_id, ";
        $tmp_groupby_date = "GROUP BY contr.currency, eci.".$batch_sql_name.", ct.name, contr.campaign_id, contr.payment_instrument_id ";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, eci.".$batch_sql_name.", ct.name, contr.campaign_id, contr.payment_instrument_id ";
      }else if($summarize_by == 'daily,pi'){
        $tmp_select_date = "ov.label as pay_type_label,  date(contr.receive_date) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name, group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_refund_select_date = "ov.label as pay_type_label,  date(contr.cancel_date) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name, group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_groupby_date = "GROUP BY contr.currency, date(contr.receive_date), contr.payment_instrument_id";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, date(contr.cancel_date), contr.payment_instrument_id";
      }else if($summarize_by == 'daily_alone'){
        $tmp_select_date = "group_concat(distinct ov.label ) as pay_type_label,  date(contr.receive_date) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name, group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
           $tmp_refund_select_date = "group_concat(distinct ov.label ) as pay_type_label,  date(contr.cancel_date) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name, group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_groupby_date = "GROUP BY contr.currency, date(contr.receive_date)";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, date(contr.cancel_date)";

      }else if($summarize_by == 'weekly,pi'){
        $tmp_select_date = "ov.label as pay_type_label, date(DATE_ADD(contr.receive_date, INTERVAL(1-DAYOFWEEK(contr.receive_date)) DAY)) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name ) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id,
                 ";
       $tmp_refund_select_date = "ov.label as pay_type_label, date(DATE_ADD(contr.cancel_date, INTERVAL(1-DAYOFWEEK(contr.cancel_date)) DAY)) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name ) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id,
                 ";
        $tmp_groupby_date = "GROUP BY contr.currency, concat(year(contr.receive_date), week(contr.receive_date, 0 )), contr.payment_instrument_id ";
        $tmp_refund_groupby_date =  "GROUP BY contr.currency, concat(year(contr.cancel_date), week(contr.cancel_date, 0 )), contr.payment_instrument_id ";

      }else if($summarize_by == 'weekly_alone'){
        $tmp_select_date = "group_concat(distinct ov.label ) as pay_type_label, date(DATE_ADD(contr.receive_date, INTERVAL(1-DAYOFWEEK(contr.receive_date)) DAY)) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name ) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id,
                 ";
        $tmp_refund_select_date = "group_concat(distinct ov.label ) as pay_type_label, date(DATE_ADD(contr.cancel_date, INTERVAL(1-DAYOFWEEK(contr.cancel_date)) DAY)) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name ) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id,
                 ";
        $tmp_groupby_date = "GROUP BY contr.currency, concat(year(contr.receive_date), week(contr.receive_date, 0 ))";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, concat(year(contr.cancel_date), week(contr.cancel_date, 0 ))";

      }else if($summarize_by == 'monthly,pi'){
        $tmp_select_date = "ov.label as pay_type_label, concat(year(contr.receive_date) , '-', month(contr.receive_date) ) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_refund_select_date = "ov.label as pay_type_label, concat(year(contr.cancel_date) , '-', month(contr.cancel_date) ) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_groupby_date = "GROUP BY contr.currency, concat(year(contr.receive_date), month(contr.receive_date)), contr.payment_instrument_id ";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, concat(year(contr.cancel_date), month(contr.cancel_date)), contr.payment_instrument_id ";

      }else if($summarize_by == 'monthly_alone'){
        $tmp_select_date = "group_concat(distinct ov.label ) as pay_type_label,  concat(year(contr.receive_date) , '-', month(contr.receive_date) ) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_refund_select_date = "group_concat(distinct ov.label ) as pay_type_label,  concat(year(contr.cancel_date) , '-', month(contr.cancel_date) ) as date, '' as financial_category,
        '' as campaign_title, '' as campaign_external_id,
        group_concat(distinct ct.name) as contribution_type_name,  group_concat( distinct ct.description) as financial_description, '' as deposit_id, '' as batch_id, ";
        $tmp_groupby_date = "GROUP BY contr.currency, concat(year(contr.receive_date), month(contr.receive_date)) ";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, concat(year(contr.cancel_date), month(contr.cancel_date)) ";

      }else if($summarize_by == 'deposit,pi'){
        $tmp_select_date = "ov.label as pay_type_label, concat(min(contr.receive_date) , '-', max(contr.receive_date) ) as date,
        '' as campaign_title, '' as campaign_external_id,
         group_concat(distinct ct.name) as contribution_type_name ,  '' as financial_category,  group_concat( distinct ct.description) as financial_description, eci.".$deposit_sql_name." as deposit_id, '' as batch_id,  ";
         $tmp_refund_select_date = "ov.label as pay_type_label, concat(min(contr.cancel_date) , '-', max(contr.cancel_date) ) as date,
        '' as campaign_title, '' as campaign_external_id,
         group_concat(distinct ct.name) as contribution_type_name ,  '' as financial_category,  group_concat( distinct ct.description) as financial_description, eci.".$deposit_sql_name." as deposit_id, '' as batch_id,  ";

        $tmp_groupby_date = "GROUP BY contr.currency, eci.".$deposit_sql_name.",  contr.payment_instrument_id ";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, eci.".$deposit_sql_name.",  contr.payment_instrument_id ";

      }
      elseif ($summarize_by == 'batch,pi') {
        $tmp_select_date = "ov.label as pay_type_label, concat(min(contr.receive_date) , '-', max(contr.receive_date) ) as date,
        '' as campaign_title, '' as campaign_external_id,
         group_concat(distinct ct.name) as contribution_type_name,   '' as financial_category,  group_concat( distinct ct.description) as financial_description,  '' as deposit_id, eci.".$batch_sql_name." as batch_id, ";
           $tmp_refund_select_date = "ov.label as pay_type_label, concat(min(contr.cancel_date) , '-', max(contr.cancel_date) ) as date,
        '' as campaign_title, '' as campaign_external_id,
         group_concat(distinct ct.name) as contribution_type_name,   '' as financial_category,  group_concat( distinct ct.description) as financial_description,  '' as deposit_id, eci.".$batch_sql_name." as batch_id, ";

        $tmp_groupby_date = "GROUP BY contr.currency, eci.".$batch_sql_name.", contr.payment_instrument_id ";
        $tmp_refund_groupby_date = "GROUP BY contr.currency, eci.".$batch_sql_name.", contr.payment_instrument_id ";
      }
      elseif($summarize_by == 'details') {
        $tmp_select_date = "ov.label as pay_type_label, date(contr.receive_date) as date,
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
         ct.name as contribution_type_name,  ".$acct_code.",   ".$financial_category_field_sql." ct.description as financial_description, '' as deposit_id, eci.".$batch_sql_name." as batch_id, ";
        $tmp_refund_select_date =  "ov.label as pay_type_label, date(contr.cancel_date) as date,
        campaign.title as campaign_title, campaign.external_identifier as campaign_external_id,
         ct.name as contribution_type_name,  ".$acct_code.",   ".$financial_category_field_sql." ct.description as financial_description, '' as deposit_id, eci.".$batch_sql_name." as batch_id, ";
        $tmp_groupby_date = "";
        $tmp_refund_groupby_date = "";
      }

      require_once('utils/finance/FinancialCategory.php');
      $tmpFinancialCategory = new FinancialCategory();
      $financial_category_field_sql = $tmpFinancialCategory->getFinancialCategoryFieldAsSQL();

      if ($summarize_by == 'details') {
        $select = $tmp_select_date." li.line_total as total_amount, c.id as contact_id,  c.sort_name,   contr.payment_instrument_id as pay_type_id,
          contr.currency, '1' as rec_count,  contr.id as contrib_id, contr.source as contrib_source, contr.check_number as check_number, contr.trxn_id as contrib_transaction_id, li.id as line_item_id ,
          CASE WHEN contr.contribution_status_id  = 1 THEN 'completed' WHEN contr.contribution_status_id  = 7 THEN 'later refunded' END as contrib_status_name";

        $refund_select =  $tmp_refund_select_date." ( 0  -  li.line_total )  as total_amount, c.id as contact_id,  c.sort_name,   contr.payment_instrument_id as pay_type_id,
          contr.currency, '1' as rec_count,  contr.id as contrib_id, contr.source as contrib_source, contr.check_number as check_number, contr.trxn_id as contrib_transaction_id, li.id as line_item_id, 'is refund' as contrib_status_name " ;
      }
      else {
        $select = $tmp_select_date . " sum(li.line_total) as total_amount, contr.currency, count(*) as rec_count";
        $refund_select = $tmp_refund_select_date . " ( 0 - sum(li.line_total)) as total_amount, contr.currency, count(*) as rec_count";
      }
    }

    $from  = $this->from();
    $where = $this->where_fancy($includeContactIDs);

    $refund_where = $this->where_fancy($includeContactIDs, 'only_refunds');

     $groupby  = $tmp_groupby_date ;   // needs alternative version for refunds.
   // $groupby = "contr.currency, ".$tmp_groupby_date." ct.name, contr.campaign_id, contr.payment_instrument_id";

    /* civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant'
       JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
       join civicrm_contribution c ON  ep.contribution_id = c.id
     */

    $non_event_contrib_sql =    "
    SELECT $select
    FROM   $from
    WHERE  $where
    $groupby ";

    $non_event_contrib_refunds_sql =    "
    SELECT $refund_select
    FROM   $from
    WHERE  $refund_where
    $tmp_refund_groupby_date ";

    $part_from = self::participant_from() ;
    $event_contrib_sql =  "
    SELECT $select
    FROM  $part_from
    WHERE  $where
    $groupby ";

    $recur_from = self::recurring_from();
    $non_event_recurring_contribs = "
    SELECT $select
    FROM $recur_from
    WHERE $where
    $groupby ";

    $sql  = "( $non_event_contrib_sql )
                UNION ALL /* EVENTCONTRIB */ ( $event_contrib_sql)
        UNION ALL /* NONEVENTCONTRIBREFUDS */ ( $non_event_contrib_refunds_sql )
        UNION ALL /* NONEVENTRECURRING */ ( $non_event_recurring_contribs ) " ;

    return $sql;
  }

  /**
    * Construct the search query
    */
  function all($offset = 0, $rowcount = 0, $sort = null, $includeContactIDs = false, $onlyIDs = false) {
    $sql = $this->prepare_sql_string($includeContactIDs, $onlyIDs);

    //for only contact ids ignore order.
    if (! $onlyIDs) {
      // Define ORDER BY for query in $sort, with default value
      if (! empty($sort)) {
        if (is_string($sort)) {
          $sql .= " ORDER BY $sort ";
        }
        else {
          $sql .= " ORDER BY " . trim($sort->orderBy());
        }
      }
      else {
          $sql .= "";
      }
    }

    if ( $rowcount > 0 && $offset >= 0 ) {
      $sql .= " LIMIT $offset, $rowcount ";
    }

    return $sql;
  }

  function recurring_from() {
    $custom_field_group_label = "Extra Contribution Info";
    $custom_field_deposit_label = "Deposit id";
    $custom_field_batch_label = "Batch id";

    $customFieldLabels = array($custom_field_deposit_label, $custom_field_batch_label);
    $extended_contrib_table = "";
    $outCustomColumnNames = array();

    require_once('utils/util_custom_fields.php');
    getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_contrib_table, $outCustomColumnNames);

    $tmp_first_contrib = " select contrib.id , contrib.contact_id ,contrib.source, contrib.currency,
   contrib.contribution_status_id,   contrib.contribution_recur_id , contrib.receive_date, contrib.total_amount, contrib.is_test
       FROM civicrm_contribution contrib
       WHERE contrib.contribution_recur_id is NOT NULL
       AND (contrib.contribution_status_id = 1 OR contrib.contribution_status_id = 2 ) ".$tmp_extra_cid."
       GROUP BY contrib.contribution_recur_id
       HAVING contrib.receive_date = min(contrib.receive_date) ";

    $tmp_from =  "
          ( ".$tmp_first_contrib.") as first_contrib JOIN civicrm_line_item li ON  li.entity_id = first_contrib.id AND li.entity_table = 'civicrm_contribution'
        join civicrm_contribution_recur recur on recur.id = first_contrib.contribution_recur_id
        JOIN civicrm_contribution contr ON contr.contribution_recur_id = recur.id
		     left join civicrm_contact c ON contr.contact_id = c.id
		LEFT JOIN civicrm_campaign campaign ON contr.campaign_id = campaign.id
		LEFT JOIN civicrm_financial_type ct ON li.financial_type_id = ct.id and ( ct.name not like 'adjustment-%' and ct.name not like '%---adjustment-%' )
		LEFT JOIN civicrm_entity_financial_account far
		ON ct.id = far.entity_id AND far.entity_table = 'civicrm_financial_type' AND far.account_relationship = 1
		   LEFT JOIN civicrm_financial_account fa ON far.financial_account_id = fa.id
		LEFT JOIN civicrm_option_value ov ON contr.payment_instrument_id = ov.value AND ov.option_group_id = 10
		LEFT JOIN ".$extended_contrib_table." eci ON contr.id = eci.entity_id
		";

    return $tmp_from;
  }

  function from() {
    $custom_field_group_label = "Extra Contribution Info";
    $custom_field_deposit_label = "Deposit id";
    $custom_field_batch_label = "Batch id" ;

    $customFieldLabels = array($custom_field_deposit_label   , $custom_field_batch_label );
    $extended_contrib_table = "";
    $outCustomColumnNames = array();

    require_once('utils/util_custom_fields.php');
    getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_contrib_table, $outCustomColumnNames ) ;

    $tmp_from =  "
        civicrm_line_item li
        JOIN civicrm_contribution contr ON  li.entity_id = contr.id AND li.entity_table = 'civicrm_contribution' AND contr.contribution_recur_id is NULL
        LEFT JOIN civicrm_contact c ON contr.contact_id = c.id
        LEFT JOIN civicrm_campaign campaign ON contr.campaign_id = campaign.id
        LEFT JOIN civicrm_financial_type ct ON li.financial_type_id = ct.id and ( ct.name not like 'adjustment-%' and ct.name not like '%---adjustment-%' )
        LEFT JOIN civicrm_entity_financial_account far
          ON ct.id = far.entity_id AND far.entity_table = 'civicrm_financial_type' AND far.account_relationship = 1
        LEFT JOIN civicrm_financial_account fa ON far.financial_account_id = fa.id
        LEFT JOIN civicrm_option_value ov ON contr.payment_instrument_id = ov.value AND ov.option_group_id = 10
        LEFT JOIN ".$extended_contrib_table." eci ON contr.id = eci.entity_id
    ";

    return $tmp_from;
  }

  function participant_from() {
    // version 4.3 specific function
    $custom_field_group_label = "Extra Contribution Info";
    $custom_field_deposit_label = "Deposit id";
    $custom_field_batch_label = "Batch id";

    $customFieldLabels = array($custom_field_deposit_label   , $custom_field_batch_label );
    $extended_contrib_table = "";
    $outCustomColumnNames = array();

    require_once('utils/util_custom_fields.php');
    getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_contrib_table, $outCustomColumnNames ) ;

    /*
    Select ct.id, ct.name, fa.accounting_code as accounting_code from civicrm_financial_type ct
         LEFT JOIN civicrm_entity_financial_account far
         ON ct.id = far.entity_id AND far.entity_table = 'civicrm_financial_type' AND far.account_relationship = 1
         LEFT JOIN civicrm_financial_account fa ON far.financial_account_id = fa.id
         */


  /*       civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant'
   JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
        join civicrm_contribution contr ON  ep.contribution_id = contr.id */

     $tmp_from =  "
         civicrm_line_item li JOIN civicrm_participant part ON li.entity_id = part.id AND li.entity_table =  'civicrm_participant'
   JOIN civicrm_participant_payment ep ON ifnull( part.registered_by_id, part.id) = ep.participant_id
        join civicrm_contribution contr ON  ep.contribution_id = contr.id
        left join civicrm_contact c ON contr.contact_id = c.id
    LEFT JOIN civicrm_campaign campaign ON contr.campaign_id = campaign.id
    LEFT JOIN civicrm_financial_type ct ON li.financial_type_id = ct.id AND ( ct.name not like 'adjustment-%' and ct.name not like '%---adjustment-%' )
    LEFT JOIN civicrm_entity_financial_account far
    ON ct.id = far.entity_id AND far.entity_table = 'civicrm_financial_type' AND far.account_relationship = 1
       LEFT JOIN civicrm_financial_account fa ON far.financial_account_id = fa.id
    LEFT JOIN civicrm_option_value ov ON contr.payment_instrument_id = ov.value AND ov.option_group_id = 10
    LEFT JOIN ".$extended_contrib_table." eci ON contr.id = eci.entity_id
    ";

   return $tmp_from;
  }

  function where($includeContactIDs = false) {
    return where_fancy($includeContactIDs);
  }

  /**
   * WHERE clause is an array built from any required JOINS plus conditional filters based on search criteria field values
   */
  function where_fancy($includeContactIDs = false, $refund_parm) {
    $clauses = array();

    $clauses[] = "contr.is_test <> 1";  // Not a test transaction

    if ($refund_parm == 'only_refunds') {
      $clauses[] = "contr.contribution_status_id = 7   ";
    }
    else {
      // contribution is complete or refunded
      $clauses[] = "contr.contribution_status_id IN (1, 7) ";
    }

    $clauses[] = "contr.total_amount <> 0";
    $clauses[] = "ct.id is not null ";

    $custom_field_group_label = "Extra Contribution Info";
    $custom_field_deposit_label = "Deposit id";
    $custom_field_batch_label = "Batch id" ;

    $customFieldLabels = array($custom_field_deposit_label, $custom_field_batch_label);
    $extended_contrib_table = "";
    $outCustomColumnNames = array();

    require_once('utils/util_custom_fields.php');
    getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_contrib_table, $outCustomColumnNames);

    $deposit_sql_name  =  $outCustomColumnNames[$custom_field_deposit_label];
    $batch_sql_name  =  $outCustomColumnNames[$custom_field_batch_label];

 	// get batch id start and end values.
 	$start_batch_id =  $this->_formValues['start_batch_id'];
 	if(strlen($start_batch_id) > 0){
 		$clauses[] = "eci.".$batch_sql_name."  >= ".$start_batch_id;
 	}

 	$end_batch_id =  $this->_formValues['end_batch_id'];
 	if(strlen($end_batch_id) > 0){
 		$clauses[] = "eci.".$batch_sql_name."  <= ".$end_batch_id;
 	}


 	// get deposit id start and end values
 	$start_deposit_id =  $this->_formValues['start_deposit_id'];
 	if(strlen($start_deposit_id) > 0){
 		$clauses[] = "eci.".$deposit_sql_name."  >= ".$start_deposit_id;
 	}

 	$end_deposit_id =  $this->_formValues['end_deposit_id'];
 	if(strlen($end_deposit_id) > 0){
 		$clauses[] = "eci.".$deposit_sql_name."  <= ".$end_deposit_id;
 	}

    $startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
     if ( $startDate ) {
         $clauses[] = "date(contr.receive_date) >= date($startDate)";
     }

     $endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
     if ( $endDate ) {
         $clauses[] = "date(contr.receive_date) <= date($endDate)";
     }

     $fin_categories = $this->_formValues['financial_category'] ;


     require_once('utils/finance/FinancialCategory.php');

     $tmp_FinancialCategory = new FinancialCategory();
     $tmp_fc =  $tmp_FinancialCategory->getContributionTypeWhereClauseForSQL( $fin_categories) ;

     if(strlen($tmp_fc) > 0 ){
       $clauses[] = $tmp_fc;
     }

     $contrib_type_ids = $this->_formValues['contrib_type'] ;

         if( ! is_array($contrib_type_ids)){
           //print "<br>No contrib type selected.";

         }
         else {
           $i = 1;
           $tmp_id_list = '';
           foreach($contrib_type_ids as $cur_id){
             if(strlen($cur_id ) > 0){
               $tmp_id_list = $tmp_id_list." '".$cur_id."'" ;
               if($i < sizeof($contrib_type_ids)){
                 $tmp_id_list = $tmp_id_list."," ;
               }
             }
             $i += 1;
           }

           if(!(empty($tmp_id_list)) ){
             $clauses[] = "ct.id IN ( ".$tmp_id_list." ) ";

           }

         //if(strlen($contrib_type_id) > 0){
          //  $clauses[] = "f1.contrib_type_id = '".$contrib_type_id."' ";
        // }
       }


       // Check user choice of accounting code.
       $accounting_codes = $this->_formValues['accounting_code'] ;

         if( ! is_array($accounting_codes)){

         	//print "<br>No accounting code selected.";


         }else if(is_array($accounting_codes)) {
           //print "<br>accounting codes: ";
           //print_r($accounting_codes);
           $i = 1;
           $tmp_id_list = '';

           foreach($accounting_codes as $cur_id){
             if(strlen($cur_id ) > 0){
               $tmp_id_list = $tmp_id_list." '".$cur_id."'" ;


               if($i < sizeof($accounting_codes)){
                 $tmp_id_list = $tmp_id_list."," ;
               }
             }
             $i += 1;
           }


           if(!(empty($tmp_id_list))  ){
             //print "<br><br>id list: ".$tmp_id_list;

             $acct_code = " fa.accounting_code";

             $clauses[] = $acct_code." IN ( ".$tmp_id_list." ) ";

           }
    	}



    	 /* Get payment instrument */
    	 //'payment_instruments'
    	 $payment_instruments = $this->_formValues['payment_instruments'] ;

         if( ! is_array($payment_instruments)){

           //print "<br>No payment instrument selected.";

         }else if(is_array($payment_instruments)) {
           //print "<br>accounting codes: ";
           //print_r($accounting_codes);
           $i = 1;
           $tmp_id_list = '';

           foreach($payment_instruments as $cur_id){
             if(strlen($cur_id ) > 0){
               $tmp_id_list = $tmp_id_list." '".$cur_id."'" ;


               if($i < sizeof($payment_instruments)){
                 $tmp_id_list = $tmp_id_list."," ;
               }
             }
             $i += 1;
           }


           if(!(empty($tmp_id_list))  ){
             //print "<br><br>id list: ".$tmp_id_list;
             $clauses[] = "contr.payment_instrument_id IN ( ".$tmp_id_list." ) ";
             //print "<br>";
             //print_r ($clauses);

           }
       }

        $where_clause = implode( ' AND ', $clauses );

        return $where_clause;
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

  function &columns() {
    return $this->_columns;
  }

  function setTitle($title) {
    if ($title) {
      CRM_Utils_System::setTitle( $title );
    }
    else {
      CRM_Utils_System::setTitle(ts('Search'));
    }
  }

  function summary() {
    require_once 'utils/util_money.php';
    if (pogstone_is_user_authorized('access CiviContribute') == false) {
      return;
    }

    $sum_array = array();
    $grand_totals = true;

    //$select = "currency, sum(line_total) as total_amount, count(*) as rec_count";
    $sql_inner  = $this->prepare_sql_string(  $includeContactIDs, $onlyIDs  ) ;
    $sql  = " SELECT t1.currency, sum(t1.total_amount) as total_amount, count(*) as rec_count FROM ( ".$sql_inner." ) as t1 GROUP BY currency ";

    $dao = CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    while ($dao->fetch()) {
       $cur_sum = array();

       $cur_sum['Currency'] = $dao->currency;
       $cur_sum['Total Amount'] = $dao->total_amount;
       $cur_sum['Records Combined'] = $dao->rec_count;

       $sum_array[] = $cur_sum;
    }

    $dao->free();
    return $sum_array;
  }

}
