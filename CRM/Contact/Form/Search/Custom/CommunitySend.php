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

class CRM_Contact_Form_Search_Custom_CommunitySend extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

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
       			ts('Recipient Name') => 'sort_name',
       			ts('Joint Greeting') => 'joint_greeting',  	
			ts('Sender Name(s)') => 'display_name_sender',
			ts('Sender Joint Greeting(s)') => 'sender_joint_greetings',
			ts('Recipient Address') => 'street_address',
			ts('Address Line 2') => 'supplemental_address_1',
			ts('City') => 'city', 
			ts('State/Province') => 'state', 
			ts('Postal/Zip Code') => 'postal_code', 
			ts('Country') => 'country', 
			ts('Recipient Email') => 'email', 
			ts('Recipient Contact ID') => 'contact_id', 
			ts('Contribution Page') => 'contrib_page_title',
			ts('Contribution Date(s)') => 'contrib_date',
			ts('Sender Contact IDs') => 'sender_ids',
					
			 );				  		
    		
    		$this->_columns = $all_cols ; 
    		
    		
    }



    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Communty Send');

        /**
         * Define the search form fields here
         */



         $commsend_pages = array();
         $commsend_pages[''] = " -- select -- "; 
            
         $params = array(
	  'version' => 3,
	  'sequential' => 1,
	  'is_active' => 1,
	  'options' =>  array('limit' => 500) , 
	);
	$result = civicrm_api('ContributionPage', 'get', $params);	
	if( $result['is_error'] <> 0 ){
	    print "<br><br>Error calling get API for ContributionPage";
	    print_r($result); 
	}else{
		   $contrib_pages = $result['values'] ; 
		   foreach($contrib_pages as $cur){
		   	if( (strpos( strtolower($cur['title']) , 'community send') !== FALSE)     ){
			    $key = $cur['id'] ;
			    $label =  $cur['title'].' (id: '.$cur['id'].')'; 
			     $commsend_pages[$key] = $label; 
			   
			   }
		   }	   
	   }
	       
       
                    
   
                    
                    
        require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	//$group_ids = $searchTools::getRegularGroupsforSelectList();
	
        $group_ids =   CRM_Core_PseudoConstant::group();  
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
        
        
        
         $form->add  ('select', 'contribution_page_choice', ts('Contribution Page'),
                     $commsend_pages,
                     false);
        
        
         $form->add('select', 'group_of_contact', ts('Contact is in the group'), $group_ids, FALSE,
          array('id' => 'group_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );
        
        
          $org_ids = $searchTools->getMembershipOrgsforSelectList();
        $form->add('select', 'membership_org_of_contact', ts('Contact has Membership In'), $org_ids, FALSE,
          array('id' => 'membership_org_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );
        
	
	$mem_ids = $searchTools->getMembershipsforSelectList();

 $form->add('select', 'membership_type_of_contact', ts('Contact has the membership of type'), $mem_ids, FALSE,
          array('id' => 'membership_type_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );
        
          
         
                     
       
	
	$comm_prefs =  $searchTools->getCommunicationPreferencesForSelectList();
        
         $comm_prefs_select = $form->add  ('select', 'comm_prefs', ts('Communication Preference'),
         	      $comm_prefs, 
                     false);  


        $summary_choices = array('' => '-- select --', 'contact' => 'Contact', 'household' => 'Household'  );
        $form->add  ('select', 'summary_type', ts('Summarize By'),
                     $summary_choices,
                     false);    
                     
        $order_choices = array('' => '-- select --' , 'name' => 'Alphabetical by Name', 'postal_code' => 'Postal/Zip Code');
        $form->add  ('select', 'order_type', ts('Order By'),
                     $order_choices,
                     false);
                     
                     
        $form->addDate('start_date', ts('Contribution Date From'), false, array( 'formatType' => 'custom' ) );
        
        $form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );                
       
        $form->assign( 'elements', array( 'contribution_page_choice', 'start_date', 'end_date'  ) );


    }

   
    function templateFile( ) {
       return 'CRM/Contact/Form/Search/Custom/Sample.tpl';
    }
       
           
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = FALSE, $onlyIDs = FALSE ) {
        
        // SELECT clause must include contact_id as an alias for civicrm_contact.id
        
  
	$this->_sort = $sort; 
	/******************************************************************************/
	// Get data for contacts 

	$groupby = " GROUP BY cp.id, contact_a.id "; 

require_once('utils/CommunitySend.php');
    		$tmpCommSend = new CommunitySend() ; 
    		$tmpCommSend::fill_temp_table() ; 
    		$full_select = " group_concat( distinct contact_sender.display_name ORDER BY contact_sender.display_name SEPARATOR ', ')  as display_name_sender, 
 contact_a.id as contact_id ,
    		contact_a.display_name as display_name,  contact_a.sort_name as sort_name,  group_concat( distinct date(contrib.receive_date)) as contrib_date, 
    		cp.title as contrib_page_title, a.street_address, a.supplemental_address_1,  a.city,
    		st.abbreviation as state, a.postal_code, country.name as country,  e.email,
    		group_concat( distinct contact_sender.id ORDER BY contact_sender.display_name SEPARATOR ',')  as sender_ids  "; 

	if ( $onlyIDs ) {
		 
        	$select  = "contact_a.id as contact_id";
        	
    	} else {
    	
    		
    		$select = $full_select;
    		
   		
     		
		
	}
	
	// make sure selected smart groups are cached in the cache table
	$group_of_contact = $this->_formValues['group_of_contact'];
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	$searchTools::verifyGroupCacheTable($group_of_contact ) ;
	
	
	$from  = $this->from( );
 	$where = $this->where( $includeContactIDs ) ; 


        $downstream_sql = "SELECT $full_select 
	FROM $from 
	WHERE $where 
	".$groupby.
	" ORDER BY contact_a.id ";
	
        $sql = "SELECT $select
		FROM  $from
		WHERE $where
		".$groupby;
//order by month(birth_date), oc_day";
	
	//for only contact ids ignore order.
     // if ( !$onlyIDs ) {
       if ( true ) {
          // Define ORDER BY for query in $sort, with default value
          /*
          if ( ! empty( $sort ) ) {
              if ( is_string( $sort ) ) {
                  $sql .= " ORDER BY $sort ";
              } else {
                  $sql .= " ORDER BY " . trim( $sort->orderBy() );
              }
          } else {
          */
               $order_type = $this->_formValues['order_type']; 
               if( $order_type == 'name' ){
               	$sql .= " ORDER BY contact_a.sort_name " ; 
               }else{
                $sql .=   " ORDER BY contact_a.sort_name ";
              }
         // }
      }

  	if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }

	if( $onlyIDs ){
	  // print "<br>SQL: ".$sql;
	   }

     /****  Put the sql statement in the session so it is avilable to downstream logic, such as tokens   ****/ 
      //  print "<br>downstream sql:<br> $downstream_sql ";
        $_SESSION['pogstone_communitysend_sql'] =''; 
        $_SESSION['pogstone_communitysend_sql'] =  $downstream_sql;

  // print "<br><br>regular sql: <br>".$sql; 
        return $sql;
    }
    
  function from(){
  
  	$tmp_from = ""; 
  	$tmp_group_join = "";
  	if(count( $this->_formValues['group_of_contact'] ) > 0 ){
  		$tmp_group_join = "LEFT JOIN civicrm_group_contact as groups on contact_a.id = groups.contact_id".
  				  " LEFT JOIN civicrm_group_contact_cache as groupcache ON contact_a.id = groupcache.contact_id "; 
  		
  	
  	
  	}
  	
  	
  	$tmp_mem_join = "";
  	if( count( $this->_formValues['membership_type_of_contact'] ) > 0 || count( $this->_formValues['membership_org_of_contact'] ) > 0     ){
  		$tmp_mem_join = "LEFT JOIN civicrm_membership as memberships on contact_a.id = memberships.contact_id
	 	LEFT JOIN civicrm_membership_status as mem_status on memberships.status_id = mem_status.id
	 	LEFT JOIN civicrm_membership_type mt ON memberships.membership_type_id = mt.id ";
  	
  	}
  
  	
  	
  	if(strlen( $comm_prefs = $this->_formValues['comm_prefs']) > 0  ){
  		$tmp_email_join = "LEFT JOIN civicrm_email ON contact_a.id = civicrm_email.contact_id AND civicrm_email.is_primary = 1 "; 
  	}
  	$tmp_from = " pogstone_temp_communitysend commsend 
  		JOIN civicrm_contact contact_a ON commsend.contact_id_recipient = contact_a.id
  		JOIN civicrm_contact contact_sender ON commsend.contact_id_sender = contact_sender.id
  		JOIN civicrm_contribution contrib ON commsend.entity_id = contrib.id AND commsend.entity_table = 'civicrm_contribution'
  		LEFT JOIN civicrm_contribution_page cp ON contrib.contribution_page_id = cp.id
  		LEFT JOIN civicrm_address a ON a.contact_id = contact_a.id AND a.is_primary = 1
  		LEFT JOIN civicrm_state_province st ON a.state_province_id = st.id
  		LEFT JOIN civicrm_country country ON a.country_id = country.id
  		LEFT JOIN civicrm_email e ON e.contact_id = contact_a.id AND e.is_primary = 1 
  	               ".
  	          $tmp_email_join.$tmp_group_join.$tmp_mem_join; 
  		  	
	return $tmp_from ; 
}
 
  function where($includeContactIDs = false){ 

	$clauses = array( );
	
	
	$clauses[] = "contact_a.is_deleted <> 1";
	
	$clauses[] = "contact_a.is_deceased <> 1";

	 $startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
	     if ( $startDate ) {
	         $clauses[] = "date(contrib.receive_date) >= date( $startDate )";
	     }
	
	     $endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
	     if ( $endDate ) {
	         $clauses[] = "date(contrib.receive_date) <= date( $endDate ) ";
	     }
	
	
	$groups_of_individual = $this->_formValues['group_of_contact'];
	
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	
	
	$comm_prefs = $this->_formValues['comm_prefs'];
	
	$searchTools->updateWhereClauseForCommPrefs($comm_prefs, $clauses ) ; 
	
	$tmp_sql_list = $searchTools->getSQLStringFromArray($groups_of_individual);
	if(strlen($tmp_sql_list) > 0 ){
	
	      // need to check regular groups as well as smart groups. 
		$clauses[] = "( (groups.group_id IN (".$tmp_sql_list.") AND groups.status = 'Added') OR ( groupcache.group_id IN (".$tmp_sql_list.")  )) " ;
		
		
	}
	
	$membership_types_of_con = $this->_formValues['membership_type_of_contact'];
	
	
	$tmp_membership_sql_list = $searchTools->convertArrayToSqlString( $membership_types_of_con ) ; 
	if(strlen($tmp_membership_sql_list) > 0 ){
		$clauses[] = "memberships.membership_type_id IN (".$tmp_membership_sql_list.")" ;
		$clauses[] = "mem_status.is_current_member = '1'";
		$clauses[] = "mem_status.is_active = '1'"; 
	
	} 
	
	// 'membership_org_of_contact'
	$membership_org_of_con = $this->_formValues['membership_org_of_contact'];
	$tmp_membership_org_sql_list = $searchTools->convertArrayToSqlString( $membership_org_of_con ) ; 
	if(strlen($tmp_membership_org_sql_list) > 0 ){
		
			$clauses[] = "mt.member_of_contact_id IN (".$tmp_membership_org_sql_list.")" ;
			$clauses[] = "mt.is_active = '1'" ; 
			$clauses[] = "mem_status.is_current_member = '1'";
			$clauses[] = "mem_status.is_active = '1'"; 	
	
	} 
	
	
	
	$contribution_page_choice = $this->_formValues['contribution_page_choice']; 
	if(strlen($contribution_page_choice) > 0 ){
		
			$clauses[] = "cp.id = ".$contribution_page_choice ;
			
	}


	

	if ( $includeContactIDs ) {
         $contactIDs = array( );
         foreach ( $this->_formValues as $id => $value ) {
             if ( $value &&
                  substr( $id, 0, CRM_Core_Form::CB_PREFIX_LEN ) == CRM_Core_Form::CB_PREFIX ) {
                 $contactIDs[] = substr( $id, CRM_Core_Form::CB_PREFIX_LEN );
             }
         }

         if ( ! empty( $contactIDs ) ) {
                $contactIDs = implode( ', ', $contactIDs );
                $clauses[] = "contact_a.id IN ( $contactIDs )";
            }
        }
        
	 $partial_where_clause = implode( ' AND ', $clauses );

	return $partial_where_clause ;


 }	
 
   function alterRow( &$row ) {
   
   $params = array(
  'version' => 3,
  'sequential' => 1,
  'contact_id' => $row['contact_id'],
);
$result = civicrm_api('JointGreetings', 'getsingle', $params);

 $row['joint_greeting'] = $result['greetings.joint_casual']; 
 
 
 $tmp_sender_ids = $row['sender_ids'];
 
 $sender_ids_array = explode( ",", $tmp_sender_ids);
 
 $tmp_sender_joint_greetings = array(); 
 
 foreach( $sender_ids_array as $cur ){
 
     $params = array(
  'version' => 3,
  'sequential' => 1,
  'contact_id' => $cur,
);
$result = civicrm_api('JointGreetings', 'getsingle', $params);

 $tmp_greeting = $result['greetings.joint_casual']; 
 
 
   $tmp_sender_joint_greetings[] = $tmp_greeting;
 
 }

  $row['sender_joint_greetings'] = implode( ', ' , $tmp_sender_joint_greetings);
  

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
