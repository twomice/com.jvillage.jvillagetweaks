<?php

require_once 'CRM/Contact/Form/Search/Custom/Base.php';

class CRM_Contact_Form_Search_Custom_ParticipantExclusion extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {
    protected $_formValues;
    protected $groupby_string ;
    
     function __construct( &$formValues ) {
        parent::__construct( $formValues );

    	 	
	
        $this->setColumns( );

        

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

   
    function buildForm( &$form ) {
	
        /**
         * You can define a custom title for the search form
         */
         $this->setTitle('Event Participant Exclusion - Contacts who have NOT participated in certain events');
         
         /**
         * if you are using the standard template, this array tells the template what elements
         * are part of the search criteria
         */
       
         require_once 'utils/util_money.php';
       if ( pogstone_is_user_authorized('access CiviEvent') == false ){
      		 $this->setTitle('Not Authorized');
       		return; 
       
       }
        
        
   	
   	/* Make sure user can filter on groups and memberships  */
   	   require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	//$group_ids = $searchTools->getRegularGroupsforSelectList();
	
	 $group_ids =   CRM_Core_PseudoConstant::group(); 
          
         $form->add('select', 'group_of_contact', ts('Contact is in the group'), $group_ids, FALSE,
          array('id' => 'group_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );
        
        
         $mem_ids = $searchTools->getMembershipsforSelectList();
       	
       	
        
        $form->add('select', 'membership_type_of_contact', ts('Contact has the membership of type'), $mem_ids, FALSE,
          array('id' => 'membership_type_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );
        
         $org_ids = $searchTools->getMembershipOrgsforSelectList();
        $form->add('select', 'membership_org_of_contact', ts('Contact has Membership In'), $org_ids, FALSE,
          array('id' => 'membership_org_of_contact', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );
        /* end of filters for groups and memberships  */
   	
   	$tmp_event_choices = self::getEventsWithParticipants();
   	       $form->add('select', 'event_id', ts('Event(s)'), $tmp_event_choices, FALSE,
          array('id' => 'event_id', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );    
                   
                   
        $tmp_event_types = self::get_event_types();
         $form->add('select', 'event_types', ts('Event Type(s)'), $tmp_event_types, FALSE,
          array('id' => 'event_types', 'multiple' => 'multiple', 'title' => ts('-- select --'))
        );    
          
                     
       $form->addDate('start_date', ts('Events From'), false, array( 'formatType' => 'custom' ) );
        
        $form->addDate('end_date', ts('...through'), false, array( 'formatType' => 'custom' ) );              
                     
       $form->assign( 'elements', array( 'group_of_contact', 'membership_org_of_contact' , 'membership_type_of_contact' ,'start_date', 'end_date',
       		'event_types', 
        	 'event_id') );                  
    
   
   
    }

    function setColumns( ) {
    
        $this->_columns = array( ts('' )    		=> 'contact_image', 
      				ts('Name') 		=> 'sort_name', 
      				ts('Age')		=> 'age', 
                                ts('Phone') 		=> 'phone',       
                                ts('Email')		=> 'email', 
                                ts('Street Address') => 'street_address',
                                ts('City')		=> 'city',
                                ts('State/Province')	=> 'state',
                                ts('Postal Code') 	=> 'postal_code',  
                                ts('Country') => 'country',  
                            
                                 );
        
    }

  


    function select($summary_section = false, $onlyIDs){
    
    
        
       
        
        
    return $select; 
    
    }
   // return $this->all( $offset, $rowcount, $sort, false, true );
   
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false ) {
       
          // check authority of end-user
       require_once 'utils/util_money.php';
       if ( pogstone_is_user_authorized('access CiviEvent') == false ){
       		return "select contact_a.id as contact_id from civicrm_contact contact_a where 1=0 "; 
       		
       }
       
       
     
       // Force summarize by layout, for exlusion does not make sense otherwise
        
       
    	
       // make sure selected smart groups are cached in the cache table
	$group_of_contact = $this->_formValues['group_of_contact'];
	
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	$searchTools::verifyGroupCacheTable($group_of_contact ) ;
   
        $where = $this->where(); 
      
      
 	
 	$groups_of_contact = $this->_formValues['group_of_contact'];
 	$mem_types_of_contact  = $this->_formValues['membership_type_of_contact'] ; 
 	$mem_orgs_of_contact  =  $this->_formValues['membership_org_of_contact'] ; 
 	
 	
      $sql_inner_participants = self::get_participant_sql();
      
      
      
   //   print "<br><br> sql: ".$sql;
   	 if ( $onlyIDs ) {
      	 	$outer_select =  "contact_a.id as contact_id";
      	 }else{
      	 	$outer_select = "contact_b.* , contact_a.sort_name, address.street_address, address.city, state.abbreviation as state,  address.postal_code, country.name as country, email.email, phone.phone";
      	 
      	 
      	 }
      	 $sql_inner = self::get_sql_contacts_to_include(); 
	
	// print "<br><br> inner sql: ".$sql_inner;
       
   
       
        $sql  = "SELECT ".$outer_select." FROM ($sql_inner
        ) as contact_b
        LEFT JOIN civicrm_email email ON contact_b.contact_id = email.contact_id AND email.is_primary = 1
        LEFT JOIN civicrm_phone phone ON contact_b.contact_id = phone.contact_id AND phone.is_primary = 1
        LEFT JOIN civicrm_address address ON contact_b.contact_id = address.contact_id AND address.is_primary = 1
        LEFT JOIN civicrm_state_province state ON address.state_province_id = state.id
        LEFT JOIN civicrm_country country ON address.country_id = country.id
	LEFT JOIN civicrm_contact contact_a ON contact_b.contact_id = contact_a.id 
	WHERE contact_b.contact_id NOT IN ( ".$sql_inner_participants." ) 
	AND contact_a.contact_type = 'Individual'
	AND contact_a.is_deleted <> 1
	AND contact_a.is_deceased <> 1
	GROUP BY contact_id "; 

      
	
	// -- this last line required to play nice with smart groups
      // INNER JOIN civicrm_contact contact_a ON contact_a.id = r.contact_id_a
      
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
              //$sql .=   "ORDER BY contact_id, contribution_type_name";
          }
      }

  	if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }



    //print "<br><br>full sql: ". $sql;   

        return $sql;
	                  
    
                           
                         
                           
 
    }
    
    
    function getEventsWithParticipants(){
    	$events = array(); 
    	
    	$sql = "SELECT e.id as event_id, e.title as event_title, e.start_date as event_start_date,
    		 count(distinct p.id) as participant_count
    		FROM civicrm_event e JOIN civicrm_participant p ON e.id = p.event_id AND p.is_test <> 1
    		GROUP BY e.id
    		ORDER BY e.start_date desc ";
    		
    	$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;

		
	while( $dao->fetch( ) ) {
		$tmp_eid = $dao->event_id;
		$e_title = $dao->event_title;
		$e_start_date = $dao->event_start_date;
		$e_count = $dao->participant_count;
		
		
		$events[$tmp_eid] = $e_title." - ".$e_start_date." - participants: ".$e_count; 
	}
	$dao->free()	;
    	
    	return $events; 
    
    }
    function get_participant_sql(){
    
    	// deal with from clause
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
    
    
    	// set up WHERE clause
    	$clauses = array();
    	
    	$clauses[] = "contact_a.is_deleted <> 1 ";
    	$clauses[] = "contact_a.is_deceased <> 1 ";
    	$clauses[] = "p.is_test <> 1 ";
    	
    	
    	$user_start_date =  CRM_Utils_Date::processDate( $this->_formValues['start_date'] ) ;
	$user_end_date =  CRM_Utils_Date::processDate( $this->_formValues['end_date'] ) ;	
	
	if( $user_start_date ){
		$clauses[] = " date(e.start_date) >= date( ".$user_start_date." )"; 
	
	}
	
	if( $user_end_date ){
		$clauses[] = " date(e.start_date) <= date( ".$user_end_date." )"; 
	
	}
	
	
	
	
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	
	$event_ids_tmp = $this->_formValues['event_id'];
	$event_ids_sql_list = $searchTools->getSQLStringFromArray($event_ids_tmp);
	if(strlen($event_ids_sql_list) > 0 ){
		$clauses[] = "( e.id IN (".$event_ids_sql_list." )   )"; 
	
	}
	
	$event_types_tmp = $this->_formValues['event_types'];
	$event_types_sql = $searchTools->getSQLStringFromArray( $event_types_tmp ); 
	if(strlen($event_types_sql) > 0 ){
		$clauses[] = "( e.event_type_id IN (".$event_types_sql." )   )"; 
	
	}
	
	
	$groups_of_individual = $this->_formValues['group_of_contact'];
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
	
	$tmp_where = implode( ' AND ', $clauses );
    
    
    	// all done with where clause
    	
    	
    	// put together entire sql
    	$sql = "SELECT p.contact_id as contact_id 
      				FROM civicrm_participant p 
      				LEFT JOIN civicrm_event e ON p.event_id = e.id
      				LEFT JOIN civicrm_contact contact_a ON p.contact_id = contact_a.id ".$tmp_group_join.$tmp_mem_join."
      				WHERE ".$tmp_where."
      				group by p.contact_id ";
      				
      	
      //	print "<br><br>participant sql: ".$sql;
      			
      	return $sql;			
    
    }
    
    
    function get_event_types(){
    
    	$tmp_event_types = array();
    	
    	$sql = "select ov.value as type_value_id, ov.label as type_label from 
    		civicrm_option_group og LEFT JOIN civicrm_option_value ov ON og.id = ov.option_group_id AND og.name = 'event_type'
    		WHERE ov.is_active = 1
    		order by ov.name ";
    		
    			
    	$dao =& CRM_Core_DAO::executeQuery( $sql,   CRM_Core_DAO::$_nullArray ) ;

		
	while( $dao->fetch( ) ) {
	
		$value_id = $dao->type_value_id ; 
		$label = $dao->type_label; 
		
		$tmp_event_types[$value_id] = $label;
		
	
	}
	
	$dao->free();
	
	return $tmp_event_types;
	
    		
    		
    
    }
    
    function get_sql_contacts_to_include(){
    	$tmp_from = ""; 
  	$tmp_group_join = "";
  	
  	
  	/*
  	// Deal with households
	
    		$tmp_contact_sql = "rel.contact_id_b as household_id , ifnull( rel.contact_id_b, contact_a.id ) as contact_id, contact_a.id as underlying_contact_id  ";
    		
    		
    		$tmp_rel_type_ids = "7, 6";   // Household member of , Head of Household 
    		$tmp_from_sql = " LEFT JOIN civicrm_relationship rel ON contact_a.id = rel.contact_id_a AND rel.is_active = 1 AND rel.is_permission_b_a = 1 AND rel.relationship_type_id IN ( ".$tmp_rel_type_ids." ) ";
    		
	// done dealing with households
  	*/
  	
  	 $ageDate = CRM_Utils_Date::processDate( $this->_formValues['age_date'] );
		  if ( $ageDate ) {
		  	$yyyy = substr( $ageDate , 0, 4);
		  	$mm = substr( $ageDate , 4, 2);
		  	$dd = substr( $ageDate , 6, 2);
		  	
		  	$tmp = $yyyy."-".$mm."-".$dd ; 
		         $age_cutoff_date =  "'".$tmp."'";
		   }else{
		   	$age_cutoff_date = "now()";
		   
		   }
		   
    		 $tmp_age_calc = "((date_format($age_cutoff_date,'%Y') - date_format(contact_a.birth_date,'%Y')) - 
    		  (date_format($age_cutoff_date,'00-%m-%d') < date_format(contact_a.birth_date,'00-%m-%d'))) as age, ";
    		  
  	
  	$tmp_contact_sql = $tmp_age_calc." contact_a.id as contact_id "; 

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
  			$tmp_from_sql
  	          $tmp_email_join
  		  $tmp_group_join 
  		  $tmp_mem_join"; 


       
       // now do where clause
       $clauses = array( );
	
	$clauses[] = "contact_a.is_deleted <> 1";
	$clauses[] = "contact_a.is_deceased <> 1";

	$oc_month_start = $this->_formValues['oc_month_start'] ;
	$oc_month_end = $this->_formValues['oc_month_end'] ;	
	
	$oc_day_start = $this->_formValues['oc_day_start'];
	$oc_day_end = $this->_formValues['oc_day_end'];
	
	
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

	
	
	
	
	$sql = "SELECT ".$tmp_contact_sql." 
		FROM ".$tmp_from."
		WHERE ".$partial_where_clause ;
		
	//	print "<br> exclude sql inner : ".$sql;
		
	return $sql;
    
    
    
    }
    
    
    function from( ) {
    
    
  
    
        return $tmp_from;



    }

   

    function where( $includeContactIDs = false ) {
       $clauses = array( );
       
   	
	// Now check user contrib type filter
       
        $contrib_type_ids = $this->_formValues['contrib_type'] ;
        
         if( ! is_array($contrib_type_ids)){
         
         	//print "<br>No contrib type selected.";
         	
         
         }else{
         	
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
         		$clauses[] = "f1.contrib_type_id IN ( ".$tmp_id_list." ) ";
         	
         	}
         	
     		
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
         		$clauses[] = "f1.accounting_code IN ( ".$tmp_id_list." ) ";
         		//print "<br>";
         		//print_r ($clauses);
         	
         	}
         	
     		
    	 }
    	 


	$balance_choice = $this->_formValues['balance_choice'] ;
	//print "<br>balance choice: ".$balance_choice;
	if(strcmp($balance_choice, 'open_balances') == 0){
        		
        		$clauses[] = "f1.balance <> 0  ";
    	 }else if(strcmp($balance_choice, 'closed_balances') == 0){
    	 		$clauses[] = "f1.balance = 0  ";
    	 
    
    	 }
	

	// filter for f1.rec_date
	  $startDate = CRM_Utils_Date::processDate( $this->_formValues['start_date'] );
	     if ( $startDate ) {
	         $clauses[] = " date(f1.rec_date) >= date($startDate)";
	     }
	
	     $endDate = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
	     if ( $endDate ) {
	         $clauses[] = " date(f1.rec_date) <= date($endDate)";
	     }
     

	/*
	$groups_of_contact = $this->_formValues['group_of_contact'];


	// Figure out if end-user is filtering results according to groups. 
	require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	$tmp_sql_list = $searchTools->getSQLStringFromArray($groups_of_contact);
	
	if(strlen($tmp_sql_list) > 0 ){
	   $clauses[] = "( groups.group_id IN (".$tmp_sql_list.") AND groups.status = 'Added') "; 
	
	}
	//
	
	$membership_types_of_con = $this->_formValues['membership_type_of_contact'];
	
	
	$tmp_membership_sql_list = $searchTools->convertArrayToSqlString( $membership_types_of_con ) ; 
	if(strlen($tmp_membership_sql_list) > 0 ){
		$clauses[] = "memberships.membership_type_id IN (".$tmp_membership_sql_list.")" ;
		$clauses[] = "mem_status.is_current_member = '1'";
		$clauses[] = "mem_status.is_active = '1'"; 
	
	} 
	
	
	$num_days_overdue = $this->_formValues['num_days_overdue'];
	
	//print "<br>Num days overdue: ".$num_days_overdue;
	if (!(is_numeric($num_days_overdue ))){
		//print "<br><br>Error: Number of Days overdue entered is not a number: ".$num_days_overdue; 
		//return ;
	
	}else{
		if(strlen($num_days_overdue) > 0){
			//print "<br>filter given for num days overdue. ";
			
			 $end_date_parm = CRM_Utils_Date::processDate( $this->_formValues['end_date'] );
      
    
    
     			//print "<br>End date: ".$end_date_parm ; 
     			if(strlen( $end_date_parm ) > 0 ){
       
     				$iyear = substr($end_date_parm, 0, 4);
     				$imonth = substr($end_date_parm , 4, 2);
     				$iday = substr($end_date_parm, 6, 2);
     				$end_date_parm = $iyear.'-'.$imonth.'-'.$iday; 
     				
     			}
     			
			if(strlen($end_date_parm) > 0 ){
      				$base_date = "'".$end_date_parm."'";
   
  			}else{
 			  	 $base_date = "now()";	
   
  			 }
  			 $tmp = "datediff($base_date ,expected_date) >= $num_days_overdue" ;
  			// print "<br><br>tmp: ".$tmp;
			$clauses[] = $tmp;
	
		}
	}
	*/
	
       if(count($clauses) > 0){
       		 $partial_where_clause = implode( ' AND ', $clauses );
       		 $tmp_where = $partial_where_clause; 
       
       
       }else{
       	   $tmp_where = "";
       }
       
      // print "<br><br>Where: ".$tmp_where;
       return $tmp_where;
    }

    function templateFile( ) {
        return 'CRM/Contact/Form/Search/Custom.tpl';
    }

    function setDefaultValues( ) {
        return array( );
    }

    function XXalterRow( &$row ) {
         
      
         
         $row['full_date'] =$row['mm_date'].'/'.$row['dd_date'].'/'.$row['yyyy_date'];
         
         
         $type = $row['entity_type'];
         $entity_id = $row['id'];
          $total_amount = $row['total_amount'];
          $status_label = $row['status_label'];
         
         if($type == 'pledge'){
         
         		if($status_label == 'Completed'){
         /*
          $tmp_cur_line_balance = '';
           $tmp_cur_line_adjustments = '';
           $tmp_cur_line_recieved = ''; 
          
         		
           $tmp_cur_line_recieved = $total_amount; 
           $tmp_cur_line_balance = 0; 
           $tmp_cur_line_adjustments = get_pledge_adjustments_total($entity_id ) ; 
           
           */
           if(strlen($end_date_parm) > 0){
           		$tmp_cur_line_due = 0 ; 	
          	 
           } 
        
        }else if($status_label == 'Pending'  || $status_label == 'In Progress' || $status_label == 'Overdue' ){
          
          if(strlen($end_date_parm) > 0){
           	
           	$tmp_cur_line_due = get_due_to_date_amount( $entity_type , $entity_id,  $end_date_parm) ; 	
           
           	} 
        }
        
        
         }else if ($type == 'contribution'){
		         	 if($status_label == 'Completed'){
		        //   $tmp_cur_line_recieved = $total_amount; 
		        //   $tmp_cur_line_balance = 0; 
		           $tmp_cur_line_due = 0 ;
		        
		        }else if($status_label == 'Pending'){
		          // $tmp_cur_line_recieved = 0 ; 
		          // $tmp_cur_line_balance = $total_amount; 
		           if( strlen($end_date_parm) > 0){
		           	$tmp_cur_line_due = get_due_to_date_amount( $entity_type , $entity_id,  $end_date_parm) ; 	
		           }
		        }
      
         	 
         
         
         }else if($type == 'recurring'){
         	 
     
         	
         
         
         }
         
        
        
    	
    
    }
    
    function setTitle( $title ) {
        if ( $title ) {
            CRM_Utils_System::setTitle( $title );
        } else {
            CRM_Utils_System::setTitle(ts('Financial Aging'));
        }
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

   

   function summary( ) {
   
       
       
       
       
      
   }
   
   
   }
   
   
   
   ?>
