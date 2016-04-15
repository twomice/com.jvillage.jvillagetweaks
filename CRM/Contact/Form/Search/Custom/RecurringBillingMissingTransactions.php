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

class CRM_Contact_Form_Search_Custom_RecurringBillingMissingTransactions extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
  //  protected $_eventID   = null;
  //  protected $_pricesetOptionId = null;

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
     
     	$this->setTitle('Automated Recurring Billing - Contacts with Missing Transactions');
     	
     	
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
		AND valA.Name != 'Cancelled'
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
			
	 $form->addDate('start_date', ts('Expected Date From'), false, array( 'formatType' => 'custom' ) );
        
         $form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );            
                    
        //$form->assign( 'elements', array( 'status_id', 'start_date', 'end_date'  ) );
     
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
      
        
        $this->_columns = array( 
        			 ts('Name')            => 'sort_name' ,
        			 ts('Billing Name')	=> 'billing_name', 
        			 ts('Subscription ID') => 'processor_id' ,
        			 ts('Subscription Status') =>  'status_name', 
        			 ts('Expected Date')   => 'receive_date' ,
        			// ts('Contribution Type') => 'contribution_type_name', 
        			 ts('Installment Amount') => 'amount',
        			 ts('Financial Type') => 'contrib_type_name', 
        			 ts('Currency') => 'currency', 
        		         ts('Payment Processor') => 'payment_processor', 
                                 ts('Number of Installments') => 'installments',
                                 ts('Associated Records in DB (any status)') => 'count_recs', 
                                 ts('Subscription Creation Date') => 'create_date', 
                                // ts('Subscription Start Date') => 'start_date', 
                                 ts('Subscription Last Changed Date') => 'modified_date', 
                                 ts('Subscription Frequency Interval') => 'frequency_interval' ,
                                 ts('Subscription Frequency Unit' )  => 'frequency_unit',
                                  ts('Email') 	       => 'email',
                                 ts('Phone')	       => 'phone',
                                  ts('Address' )	=> 'street_address',
                                 ts('Address line 1') => 'supplemental_address_1',
                                 ts('City') 		=> 'city',
                                 ts('State') =>  'state',
                                 ts('Postal Code') => 'postal_code' ,
                                 ts('ID in DB') => 'recur_id');
                                 
                                
      
    }

  


    
  /*
  function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false ) {
  */
   
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false,  $onlyIDs = false ) {
              
              
             // check authority of end-user
       require_once 'utils/util_money.php';
       if ( pogstone_is_user_authorized('access CiviContribute') == false ){
       		return "select 'You are not authorized to this area' as total_amount from  civicrm_contact where 1=0 limit 1"; 
       		
       }
       
                
         $tmp_table_name =  self::build_recurring_payments_temp_table( );
       
       

       
         if ( $onlyIDs ) {
      	 	 $selectClause =   "contact_a.id as contact_id";
      	 }else{
      	 	 $selectClause =  "contact_a.sort_name   as sort_name, billing_address.name as billing_name, r.id AS recur_id, r.processor_id, date(contrib.receive_date) as receive_date,r.id as recur_id, r.installments, 
   r.create_date, r.start_date as start_date, contact_a.id as contact_id, valA.Name as status_name,
      r.modified_date, r.frequency_unit, r.frequency_interval, r.amount,
      civicrm_payment_processor.name as payment_processor, 
civicrm_email.email as email, civicrm_phone.phone as phone, civicrm_address.street_address as street_address, 
civicrm_address.supplemental_address_1 as supplemental_address_1, civicrm_address.city as city ,civicrm_address.postal_code as postal_code, 
civicrm_state_province.abbreviation as state,  t2.contrib_type_name, t2.count_recs, t2.currency "; 
      	 
      	 
      	 }
       
       // print "<br>sql: ".$selectClause; 
        
        
       $tmp_full_sql =  $this->sql( $selectClause,
                           $offset, $rowcount, $sort,
                           $includeContactIDs, $groupBy );
                           
    // print "<br><br>full sql: ". $tmp_full_sql;    	
                           
         return $tmp_full_sql; 
         
    
    /*************************************************************************/
         
      
   
       $tmp_contrib_where = " AND DATE(contrib.receive_date) <= now()";	
       $tmp_pledge_pay_where = " and DATE(pp.scheduled_date) <=  now()";
       $tmp_recur_where = " DATE(receive_date) <=  now()";
   	 $tmp_select_field = "DATE(now())"; 	
   
   

    }
    


    
    function from( ) {
        $from = "";
    
    		$from = " FROM 
        civicrm_option_value as valA, 
civicrm_option_group as grpA,
`civicrm_contribution_recur` r LEFT JOIN civicrm_pogstone_recurring_contribution contrib on r.id = contrib.contribution_recur_id 
LEFT JOIN civicrm_payment_processor ON r.payment_processor_id = civicrm_payment_processor.id 
JOIN civicrm_contact contact_a on r.contact_id = contact_a.id 
left join civicrm_email on contact_a.id = civicrm_email.contact_id 
left join civicrm_phone on contact_a.id = civicrm_phone.contact_id
left join civicrm_address on contact_a.id = civicrm_address.contact_id
left join civicrm_state_province on civicrm_address.state_province_id = civicrm_state_province.id  
left join civicrm_address billing_address on contrib.address_id = billing_address.id
 LEFT JOIN (
	   	select contribution_recur_id, max(financial_type_id) as contribution_type_id , ct.name as contrib_type_name, count(*) as count_recs , currency
	   	from civicrm_contribution left JOIN civicrm_financial_type ct ON civicrm_contribution.financial_type_id = ct.id group by contribution_recur_id  ) t2 ON r.id = t2.contribution_recur_id ";
	   	
    
       

	return $from; 
	

    }

    function where( $includeContactIDs = false ) {
       // print "<hr><br>Inside where function.";
       
       $tmp_where = '';
       
       
       
       /*
       $status_choices =   $this->_statusChoices; 
      
      if(strlen($status_choices) > 0){
      		$extra_sql = " AND r.contribution_status_id = ".$status_choices ; 
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
         		$extra_sql = " AND r.contribution_status_id IN ( ".$tmp_id_list." ) ";
         	}	
     		
    	 }
      
      
      
      
      
      
      
      $startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
     if ( $startDate ) {
         $extra_sql = $extra_sql." AND date(contrib.receive_date) >= date($startDate) ";
     }

     $endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
     if ( $endDate ) {
         $extra_sql = $extra_sql." AND date(contrib.receive_date) <= date($endDate) ";
     }
     
     
     
       
      //$partial_sql = "";
       
       // Cancelled subscriptions mean that a human being cancelled the subcription in the 
       // CRM back-office.  Since there is no way for an end-user to delete the subscription
       // entirely, cancelled subscriptions just clutter up the search results. 
          
       $tmp_where = "r.contribution_status_id = valA.value
		AND  valA.option_group_id = grpA.id 
		AND grpA.name = 'contribution_status' AND
		r.id = contrib.contribution_recur_id".$extra_sql."
 AND contrib.contact_id = contact_a.id 
 AND valA.Name != 'Cancelled'
 AND contrib.receive_date < DATE( now( ) )
 AND (civicrm_email.is_primary = 1 OR civicrm_email.email is null) 
       AND (civicrm_phone.is_primary = 1 OR civicrm_phone.phone is null)
       AND (civicrm_address.is_primary = 1 OR civicrm_address.street_address is null)
       AND (civicrm_state_province.abbreviation like '%' or civicrm_state_province.abbreviation is null)";

    //   print "<Hr><br>About to return where clause: ".$tmp_where; 
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
    
    }
    
    
    // TODO: Probably should move into a Pogstone utility class
    function build_recurring_payments_temp_table( ){
  
    
    	$temp_table_name = "civicrm_pogstone_recurring_contribution"; 
    
    	$sql_create_table = "create table IF NOT EXISTS ".$temp_table_name." like civicrm_contribution";
        $sql_truncate_table = "truncate ".$temp_table_name; 
    
    	$dao_a =& CRM_Core_DAO::executeQuery( $sql_create_table,   CRM_Core_DAO::$_nullArray ) ;
	
	$dao_a->free();
	
	
	$dao_b =& CRM_Core_DAO::executeQuery( $sql_truncate_table,   CRM_Core_DAO::$_nullArray ) ;
	$dao_b->free();
	
	
	$sql_str = ""; 
     //   require_once ('utils/Entitlement.php');
     //   $entitlement = new Entitlement();
        
            $sql_str = "select  t1.recur_id, t1.amount, t1.currency, t1.installments, t1.frequency_unit, t1.frequency_interval, 
   	t1.start_date, t1.financial_type_id, t1.contact_id, t2.num_completed_payments , t1.currency, t1.create_date, t1.contrib_receive_date, t1.billing_address_id
   	 from (SELECT r.id as recur_id, r.amount, r.currency, r.installments, r.frequency_unit, r.frequency_interval, r.create_date, r.start_date as start_date, 
   	date( min(c.receive_date)) as xxxstart_date, r.financial_type_id, r.contact_id, c.receive_date as contrib_receive_date, c.address_id as billing_address_id
   	FROM `civicrm_contribution_recur` r  left join civicrm_contribution c on 
	r.id = c.contribution_recur_id 
	group by r.id) as t1 left join 
	(SELECT r.id as recur_id, count(*) as num_completed_payments FROM `civicrm_contribution_recur` r, 
civicrm_contribution contrib, 
civicrm_option_value val, 
	civicrm_option_group grp
	WHERE 
	r.id = contrib.contribution_recur_id
	AND contrib.contribution_status_id = val.value
	AND  val.option_group_id = grp.id 
	AND grp.name = 'contribution_status'
	and contrib.contribution_status_id = val.value
	and val.name in ('Completed' )  
	and contrib.is_test = 0
	group by r.id) as t2
	on t1.recur_id = t2.recur_id 
	where
	(t1.installments is null || t2.num_completed_payments is null || t1.installments > t2.num_completed_payments) " ;
	
        
        
		      
   	$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
    
     	$tmp_display_name = "";
    	while ( $dao->fetch() ) {
      		$tmp_recur_id = $dao->recur_id; 
      		$tmp_recur_amount = $dao->amount;
      		$tmp_installments = $dao->installments; 
      		$tmp_frequency_unit = $dao->frequency_unit;
      		$tmp_frequency_interval = $dao->frequency_interval;
      		$tmp_start_date = $dao->start_date; 
      		$tmp_create_date = $dao->create_date;
      		
      		$tmp_contribution_type_id = "";
      		
      		$tmp_contribution_type_id = $dao->financial_type_id; 
      		
      		
      		$tmp_contact_id = $dao->contact_id; 
      		$tmp_billing_address_id = $dao->billing_address_id; 
      		
      		$tmp_completed_payments = $dao->num_completed_payments;
      		$tmp_currency = $dao->currency;
      		$tmp_contrib_receive_date = $dao->contrib_receive_date; 
      		
      		//$tmp_recur_first_payment = $tmp_start_date; 
      		//print "<br>recur id: ".$tmp_recur_id." Completed payments: ".$tmp_completed_payments;
      		// TODO: Get number of completed contributions 
      		//print "<br>Number of expected installments: ". $tmp_installments; 
      		if(strlen( $tmp_installments) == 0){
      		     $tmp_installments = 20; 
      		}
      		
      		// Insert appropriate records into temp table
      		for($i =0 ; $i < $tmp_installments; $i++){
      		    // Do date arithmetic. 	
      		    //$tmp_installment_date = "'".$tmp_start_date."'";
      		  
      		    if( $i > $tmp_completed_payments -1){
      		        $interval_num = $i * $tmp_frequency_interval;
      		        //print "<br>Start date: ".$tmp_start_date."  Contrib. receive date: ".$tmp_contrib_receive_date;
      		        if($tmp_start_date == '1969-12-31 19:00:00'){
      		        	// This means the person chose a future start date when scheduling the subscription.b
      		        	$base_date = $tmp_contrib_receive_date; 
      		        	$tmp_installment_date = "'$tmp_contrib_receive_date' + INTERVAL $interval_num $tmp_frequency_unit"  ;  //"'$tmp_contrib_receive_date'"; 
      		        }else{
      		        	$base_date = $tmp_start_date; 
      		        	$tmp_installment_date = "'$tmp_start_date' + INTERVAL $interval_num $tmp_frequency_unit";  
      		        }
      		        
      		      //  print "<Br>recur id: ".$tmp_recur_id." "." Base date: ".$base_date;
      		        
      		        
      		          if(strlen($tmp_contribution_type_id) == 0 ){
      		            $tmp_contribution_type_id = -1; 
      		            //$tmp_installment_date = "'1970-01-01'"; 
      		          }
      		    	$cur_installment_to_insert = "";
      		    	
      		    	if( strlen($tmp_billing_address_sql) == 0){
      		    	 $tmp_billing_address_sql = "NULL"; 
      		    	
      		    	} 
      		    	
      		    	 $cur_installment_to_insert = "INSERT INTO ".$temp_table_name." (contribution_recur_id, contact_id, total_amount,financial_type_id, receive_date, currency, address_id  ) 
	      		        values ($tmp_recur_id, $tmp_contact_id, $tmp_recur_amount, $tmp_contribution_type_id, $tmp_installment_date, '$tmp_currency' , NULL ) "; 
      		    	 
      		    	 
      		    //   print "<BR>new row: ".$cur_installment_to_insert ; 
      		    
      		        $dao_insert =& CRM_Core_DAO::executeQuery( $cur_installment_to_insert,   CRM_Core_DAO::$_nullArray ) ;
      		        
      		        }
      		
      		}
      		
      
    	}
    	$dao->free( ); 
        

   
   	return $tmp_table_name; 
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
