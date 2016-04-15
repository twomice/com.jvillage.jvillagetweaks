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

class CRM_Contact_Form_Search_Custom_ContactListing extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

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
				 
				 ts('City') => 'city',
				 ts('Postal Code') => 'postal_code', 
				 
				
				 );
				 
		$summary_type = $this->_formValues['summary_type']; 
		
		$this->_summary_type = $summary_type;
		
	    	if( $summary_type == 'household'){
    		     $all_cols['Contact ID'] = 'contact_id';
    		}else{
    		      $all_cols['Household Name'] = ts('hh_name');
		      $all_cols['Household ID'] = ts('hh_id') ; 
    		}
    		
    		
    		$this->_columns = $all_cols ; 
    		
    		
    }



    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle('Contact Listing');

        /**
         * Define the search form fields here
         */



            
            
       
                    
   
                    
                    
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
        
          
         
          $primary_mem_options = array();
         
        $primary_mem_options['primary_only']  = "Primary Members Only";
        $primary_mem_options['any_member'] = "Any Member (related or primary)";
        
        
          $form->add  ('select', 'membership_primary_choice', ts('Which Members?'),
                     $primary_mem_options,
                     false);            
       
	
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
                     
       
        $form->assign( 'elements', array( 'group_of_contact', 'membership_org_of_contact' , 'membership_type_of_contact' , 'membership_primary_choice',   'comm_prefs',   'summary_type', 'order_type') );


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

	$grouby = ""; 
	if ( $onlyIDs ) {
	
		$summary_type = $this->_formValues['summary_type']; 
	    	if( $summary_type == 'household'){
	    	   $groupby = " Group BY contact_id ";
	    	   $select = " if(  contact_a.contact_type = 'Household' OR  household.id is null , contact_a.id,   household.id ) as contact_id "; 
	    	
	    	}else{
        		$select  = "contact_a.id as contact_id";
        	}
    	} else {
    		
    		
    		$summary_type = $this->_formValues['summary_type']; 
	    	if( $summary_type == 'household'){
	    		$groupby = " Group BY hh_id ";
	    		$select = "
			 if(  contact_a.contact_type = 'Household' OR  household.id is null , contact_a.id,   household.id ) as contact_id,
			  if(  contact_a.contact_type = 'Household' OR  household.id is null , contact_a.id,   household.id ) as hh_id, 
			 if( contact_a.contact_type = 'Household' OR  household.id is null, contact_a.sort_name , household.sort_name) as sort_name,
			 addr.postal_code as postal_code, addr.city as city" ;
	
	
    		}else {
    		   $groupby = " Group BY contact_a.id ";
    		
    		   $select = "contact_a.id as contact_id , 
		  contact_a.sort_name as sort_name,
		 if(  contact_a.contact_type = 'Household' OR  household.id is null , contact_a.id,   household.id ) as hh_id,
		 if( contact_a.contact_type = 'Household', contact_a.sort_name , household.sort_name) as hh_name,
		  addr.postal_code as postal_code, addr.city as city" ;
		 
		 }

    		
    		
   		
     		
		
	}
	
	// make sure selected smart groups are cached in the cache table
	$group_of_contact = $this->_formValues['group_of_contact'];
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	$searchTools::verifyGroupCacheTable($group_of_contact ) ;
	
	
	$from  = $this->from( );
 	$where = $this->where( $includeContactIDs ) ; 

	//$days_after_today = ($date_range_start_tmp + $date_range_end_tmp);
	//echo "<!--  date_range: " . $date_range . " -->";
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
                $sql .=   " ORDER BY addr.postal_code ";
              }
         // }
      }

  	if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }

	if( $onlyIDs ){
	  // print "<br>SQL: ".$sql;
	   }

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
  	$tmp_from = " civicrm_contact contact_a 
  	              left join civicrm_relationship r ON r.contact_id_a = contact_a.id AND r.is_active = 1  AND r.relationship_type_id IN ( 6, 7 )
  	              LEFT JOIN civicrm_contact household ON r.contact_id_b = household.id AND household.is_deleted <> 1
  	              LEFT JOIN civicrm_address addr ON contact_a.id = addr.contact_id AND addr.is_primary = 1 ".
  	          $tmp_email_join.$tmp_group_join.$tmp_mem_join; 
  		  	
	return $tmp_from ; 
}
 
  function where($includeContactIDs = false){ 

	$clauses = array( );
	
	$clauses[] = "contact_a.is_deleted <> 1";
	
	$clauses[] = "contact_a.is_deceased <> 1";

	
	
	
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
	
	$primary_member_choice = $this->_formValues['membership_primary_choice'];
	
	$primary_mem_filter = ""; 
	if( $primary_member_choice == "primary_only" ){
	
		$primary_mem_filter = " memberships.owner_membership_id is NULL ";  // is the primary member
	
	}else{
	
	}
	
	$membership_types_of_con = $this->_formValues['membership_type_of_contact'];
	
	
	$tmp_membership_sql_list = $searchTools->convertArrayToSqlString( $membership_types_of_con ) ; 
	if(strlen($tmp_membership_sql_list) > 0 ){
		$clauses[] = "memberships.membership_type_id IN (".$tmp_membership_sql_list.")" ;
		$clauses[] = "mem_status.is_current_member = '1'";
		$clauses[] = "mem_status.is_active = '1'"; 
		if( strlen( $primary_mem_filter) > 0 ){
				$clauses[] = $primary_mem_filter;  
			}
	
	} 
	
	// 'membership_org_of_contact'
	$membership_org_of_con = $this->_formValues['membership_org_of_contact'];
	$tmp_membership_org_sql_list = $searchTools->convertArrayToSqlString( $membership_org_of_con ) ; 
	if(strlen($tmp_membership_org_sql_list) > 0 ){
		
			$clauses[] = "mt.member_of_contact_id IN (".$tmp_membership_org_sql_list.")" ;
			$clauses[] = "mt.is_active = '1'" ; 
			$clauses[] = "mem_status.is_current_member = '1'";
			$clauses[] = "mem_status.is_active = '1'"; 
			
			if( strlen( $primary_mem_filter) > 0 ){
				$clauses[] = $primary_mem_filter; 
			}
	
	} 
	
	
	
	$cal_year = $this->_formValues['cal_year']; 
	if( ($cal_year <> '' ) && is_numeric ($cal_year)){
		$clauses[] =  "YEAR( contrib.receive_date) = ".$cal_year ;
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

  function alterRowXXX( &$row ) {
		// URL to view contrib. detail: /civicrm/contact/view/contribution?reset=1&id=19617&cid=1297&action=view&context=contribution&selectedChild=contribute
		// display hyperlink if contribution id is available. 
		//if( $row['age'] == '0'){
		 	//$row['age'] = "Infant (Less Than 1)";
		 
		// }
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
