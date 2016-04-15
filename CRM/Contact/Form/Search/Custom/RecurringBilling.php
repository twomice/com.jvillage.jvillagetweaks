<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 3.4                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CRM/Contact/Form/Search/Custom/Base.php';

class CRM_Contact_Form_Search_Custom_RecurringBilling extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
    protected $_statusChoices = null;  
    protected $_userChoices = null; 

    function __construct( &$formValues ) {
        parent::__construct( $formValues );

  	
	
        $this->setColumns( );
	
	$this->_statusChoices = CRM_Utils_Array::value( 'status_id',
                                                  $this->_formValues );
	
	//print_r($this->_userChoices);
	
	//print "<br>all events: ";
	//print_r($tmp_all_events);
	
	//print "<br>all priceset options: ";
	//print_r($tmp_all_priceset_options); 
	
	//$tmpEventId = $form_values[0];
	//$tmp_priceset_id = $form_values[1];

    }
    
     function buildForm( &$form ) {
     
     	$this->setTitle('Automated Recurring Billing');
     	
     	  require_once 'utils/util_money.php';
       if ( pogstone_is_user_authorized('access CiviContribute') == false ){
      		 $this->setTitle('Not Authorized');
       		return; 
       
       }
     	
     	$status_choices = array();
     	$status_choices[''] = "-- Select Status --"; 
	
	$sql = "Select valA.value, valA.Name as status_name 
		FROM   
		civicrm_option_value as valA, civicrm_option_group as grpA
		WHERE valA.option_group_id = grpA.id 
		AND grpA.name = 'contribution_status' 
		ORDER BY valA.Name ";
		  
	$status_dao = & CRM_Core_DAO::executeQuery( $sql, CRM_Core_DAO::$_nullArray );
                                             
         while ($status_dao->fetch()){
         	
              $cur_val = $status_dao->value;
              $cur_name = $status_dao->status_name; 
              $status_choices[$cur_val] = $cur_name;
         
         }
         
        $status_dao->free();                                    
	
	$tmp_select = $form->add( 'select',
                    'status_id',
                    ts( 'Subscription Status' ),
                    $status_choices,
                    false );
                    
        $tmp_select->setMultiple(true); 
                    
                    
         $date_options = array(
          'language'  => 'en',
          'formatType'    => 'dMY',
          'minYear'   => 2011,
          'maxYear'   => 2012
      );
			
	$form->addDate('start_date', ts('Creation Date From'), false, array( 'formatType' => 'custom' ) );
        
        $form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );            
                    
        $form->assign( 'elements', array( 'status_id', 'start_date', 'end_date'  ) );
     
     
     }
     
     

    function __destruct( ) {
        /*
        if ( $this->_eventID ) {
            $sql = "DROP TEMPORARY TABLE {$this->_tableName}";
            CRM_Core_DAO::executeQuery( $sql );
        }
        */
    }


/***********************************************************************************************/

       

    
    function setColumns( ) {
        
        
        $this->_columns = array( ts('Subscription ID') => 'processor_id' ,
        			 ts('Name')            => 'sort_name' ,
        			// ts('Billing Name')    => 'billing_name', 
        			ts('ID in DB') => 'recur_id', 
        			ts('Recurring Billing Status') =>  'status_name', 
        			ts('Payment Processor') => 'payment_processor',
        			ts('Associated Records in DB') => 'count_recs', 
                                 ts('Installment Amount') => 'amount', 
                                 ts('Financial Type') => 'contrib_type_name', 
                                 ts('Currency') => 'currency', 
                                 ts('Number of Installments') => 'installments',
                                 ts('Creation Date') => 'create_date', 
                                 ts('Start Date') => 'start_date', 
                                 ts('Last Changed Date') => 'modified_date', 
                                 ts('Frequency Unit' )  => 'frequency_unit',
                                 ts('Frequency Interval') => 'frequency_interval',		
                                // ts('Invoice ID') => 'invoice_id',
                                 ts('Email') 	       => 'email',
                                 ts('Phone')	       => 'phone',
                                 ts('Address' )	=> 'street_address',
                                 ts('Address line 1') => 'supplemental_address_1',
                                 ts('City') 		=> 'city',
                                 ts('State') =>  'state',
                                 ts('Postal Code') => 'postal_code' ); 
      
    }

 
/*
  function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false ) {
  */
   
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false  ) {
       
       
           // check authority of end-user
       require_once 'utils/util_money.php';
       if ( pogstone_is_user_authorized('access CiviContribute') == false ){
       		return "select 'You are not authorized to this area' as total_amount from  civicrm_contact where 1=0 limit 1"; 
       		
       }
       
        $selectClause = "Distinct recur.id as recur_id, recur.contact_id as contact_id  , recur.create_date, 
         recur.modified_date, recur.frequency_unit,
         recur.frequency_interval, recur.processor_id,
         recur.amount, recur.installments,   
         ct.name as contrib_type_name, 
         recur.currency , 
         if(recur.auto_renew, 'Yes', 'No' ) as auto_renew,
          t2.billing_name as billing_name, 
         valA.Name as status_name,
contact_a.sort_name   as sort_name, civicrm_payment_processor.name as payment_processor, 
civicrm_email.email as email, civicrm_phone.phone as phone, civicrm_address.street_address as street_address, 
civicrm_address.supplemental_address_1 as supplemental_address_1, civicrm_address.city as city ,civicrm_address.postal_code as postal_code, 
civicrm_state_province.abbreviation as state,  t2.count_recs, t2.start_date
 "; 

       
        
        
       // print "<br>sql: ".$selectClause; 
        
        $groupBy = " group by recur.id "  ; 
       $tmp_full_sql =  $this->sql( $selectClause,
                           $offset, $rowcount, $sort,
                           $includeContactIDs, $groupBy );
                           
     //   print "<br><br>full sql: ". $tmp_full_sql;    	
                           
         return $tmp_full_sql; 
                           
                           

    }
    
  // left join civicrm_address billing_address on t2.address_id = billing_address.id
    
    function from( ) {
    	$from  = "";
    
    	
  		$from  = " FROM   
		civicrm_option_value as valA, 
		civicrm_option_group as grpA, civicrm_contribution_recur recur 
		left JOIN civicrm_financial_type ct ON recur.financial_type_id = ct.id
		Left Join civicrm_payment_processor ON recur.payment_processor_id = civicrm_payment_processor.id 
		JOIN civicrm_contact contact_a on recur.contact_id = contact_a.id 
		left join civicrm_email on contact_a.id = civicrm_email.contact_id 
		left join civicrm_phone on contact_a.id = civicrm_phone.contact_id
		left join civicrm_address on contact_a.id = civicrm_address.contact_id
		left join civicrm_state_province on civicrm_address.state_province_id = civicrm_state_province.id
		LEFT JOIN (
			   	select contrib.contribution_recur_id, billing_address.name as billing_name, min(contrib.receive_date) as start_date,   	  count(contrib.id) as count_recs 
			   	from civicrm_contribution contrib  
			   	LEFT JOIN civicrm_address billing_address on contrib.address_id = billing_address.id
			   	group by contribution_recur_id  ) t2 ON recur.id = t2.contribution_recur_id
		";      
        
        
        
          
          
          return $from  ;

    }

    function where( $includeContactIDs = false ) {
       // print "<hr><br>Inside where function.";
       
       $tmp_where = '';
       
       /*
       $status_choices =   $this->_statusChoices; 
      
      if(strlen($status_choices) > 0){
      		$extra_sql = " AND recur.contribution_status_id = ".$status_choices ; 
      }else{
      		$extra_sql = ""; 
      }
      */
      
      $status_choices = $this->_formValues['status_id'] ;
        
         if( ! is_array($status_choices)){
         
         	//print "<br>No statusChoices selected.";
         	
         
         }else if(is_array($status_choices)) {
         	// print "<br>status choices: ";
         	// print_r($status_choices);
         	$i = 1;
         	$tmp_id_list = '';
         	
         	foreach($status_choices as $cur_id){
         		if(strlen($cur_id ) > 0){
         			$tmp_id_list = $tmp_id_list." '".$cur_id."'" ; 
         			
         		
	         		if($i < sizeof($status_choices)){
	         			$tmp_id_list = $tmp_id_list."," ; 
	         		}
	         	}
         		$i += 1;
         	}
         	
         	
         	if(!(empty($tmp_id_list))  ){
         		//print "<br><br>id list: ".$tmp_id_list;
         		$extra_sql = " AND recur.contribution_status_id IN ( ".$tmp_id_list." ) ";
         	}	
     		
    	 }
      
      
      
      
      
      $startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
     if ( $startDate ) {
         $extra_sql = $extra_sql." AND recur.create_date >= $startDate";
     }

     $endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
     if ( $endDate ) {
         $extra_sql = $extra_sql." AND recur.create_date <= $endDate";
     }
     
     
     
       
      $partial_sql = "";
       
       $tmp_where = $partial_sql." recur.contribution_status_id = valA.value
		AND  valA.option_group_id = grpA.id 
		AND grpA.name = 'contribution_status' AND
        (civicrm_email.is_primary = 1 OR civicrm_email.email is null) 
       AND (civicrm_phone.is_primary = 1 OR civicrm_phone.phone is null)
       AND (civicrm_address.is_primary = 1 OR civicrm_address.street_address is null)
       AND (civicrm_state_province.abbreviation like '%' or civicrm_state_province.abbreviation is null)".$extra_sql;
       
      // print "<Hr><br>About to return where clause: ".$tmp_where; 
       return $tmp_where;
    }

    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom.tpl';
    }

    function setDefaultValues( ) {
        return array( );
    } 

    function alterRow( &$row ) {
		
	if( strlen($row['installments']) == 0 ){
		$row['installments'] = 'Ongoing';
	}
	
	//$tmp = $row['billing_name']; 
	
	//str_replace('',  ' ', $tmp );
	//$row['billing_name'] = $tmp;
	
    
    }
    
    
    
    
    /* 
     * Functions below generally don't need to be modified
     */
    function count( ) {
           $sql = $this->all( );
           
           $dao = CRM_Core_DAO::executeQuery( $sql,
                                             CRM_Core_DAO::$_nullArray );
           return $dao->N;
    }
       
    function contactIDs( $offset = 0, $rowcount = 0, $sort = null) { 
        return $this->all( $offset, $rowcount, $sort, false, true );
    }
       
    function &columns( ) {
        return $this->_columns;
    }

   function setTitle( $title ) {
       if ( $title ) {
           CRM_Utils_System::setTitle( $title );
       } else {
           CRM_Utils_System::setTitle(ts('Search'));
       }
   }

   function summary( ) {
       return null;
   }
    
    
}
