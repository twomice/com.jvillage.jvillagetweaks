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

class CRM_Contact_Form_Search_Custom_UpcomingBnaiMitzvot extends CRM_Contact_Form_Search_Custom_Base implements CRM_Contact_Form_Search_Interface {

    protected $_formValues;
    protected $_tableName = null;

    function __construct( &$formValues ) {     
        $this->_formValues = $formValues;

        /**
         * Define the columns for search result rows
         */
        $this->_columns = array( 
				 ts('Name') => 'sort_name',	
				 ts('Bar/Bat Mitzvah Date') => 'bmitzvah_month_and_day',
				 ts('Bar/Bat Mitzvah Date (sortable)') => 'bmitzvah_month_and_day_sortable', 
				 ts('Birth Year') => 'birth_year',
				 ts('Current Age') => 'age',
				 ts('Occasion Type' ) => 'oc_type'
				 );
    }



    function buildForm( &$form ) {
        /**
         * You can define a custom title for the search form
         */
        $this->setTitle("Find Upcoming B'nai Mitzvot");

        /**
         * Define the search form fields here
         */


$month =
            array( ''   => ' - select month - ' , '1' => 'January', '2' => 'February', '3' => 'March',
	 '4' => 'April', '5' => 'May' , '6' => 'June', '7' => 'July', '8' => 'August' , '9' => 'September' , '10' => 'October' , '11' => 'November' , '12' => 'December') ;
            
            
        $form->add  ('select', 'oc_month_start', ts('Start With Month'),
                     $month,
                     false);

 	$form->add  ('select', 'oc_month_end', ts('Ends With Month'),
                     $month,
                     false);
       

	$form->add( 'text',
                    'oc_day_start',
                    ts( ' Start With day' ) );

	$form->add( 'text',
                    'oc_day_end',
                    ts( ' End With day' ) );
                    
                    
        require_once('utils/CustomSearchTools.php');
	$searchTools = new CustomSearchTools();
	$group_ids = $searchTools::getRegularGroupsforSelectList();
	
            
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
        
          

/*

 	$form->add( 'date',
                    'oc_start_date',
                    ts('Date From'),
                    CRM_Core_SelectValues::date('custom', 10, 3 ) );
        $form->addRule('oc_start_date', ts('Select a valid date.'), 'qfDate');

        $form->add( 'date',
                    'oc_end_date',
                    ts('...through'),
                    CRM_Core_SelectValues::date('custom', 10, 0 ) );
        $form->addRule('oc_end_date', ts('Select a valid date.'), 'qfDate');

*/
        /**
         * If you are using the sample template, this array tells the template fields to render
         * for the search form.
         */
        $form->assign( 'elements', array( 'group_of_contact', 'membership_org_of_contact' , 'membership_type_of_contact' , 'oc_month_start', 'oc_month_end', 'oc_day_start', 'oc_day_end') );


    }

    /**
     * Define the smarty template used to layout the search form and results listings.
     */
    function templateFile( ) {
       return 'CRM/Contact/Form/Search/Custom/Sample.tpl';
    }
       
    /**
      * Construct the search query
      */       
    function all( $offset = 0, $rowcount = 0, $sort = null,
                  $includeContactIDs = false, $onlyIDs = false ) {
        
        // SELECT clause must include contact_id as an alias for civicrm_contact.id
        
  
	
	/******************************************************************************/
	// Get data for contacts 

	if ( $onlyIDs ) {
        	$select  = "DISTINCT contact_a.id as contact_id";
    	} else {
    	
    		// Figure out how to format date for this locale
    		$config = CRM_Core_Config::singleton( );
	
   		$tmp_system_date_format = 	$config->dateInputFormat;
   		if($tmp_system_date_format == 'dd/mm/yy'){
		       $formatted_date_sql = " CONCAT( day(contact_a.birth_date) , ' ', monthname(contact_a.birth_date)  ) as birth_month_and_day ";
		  
		  }else if($tmp_system_date_format == 'mm/dd/yy'){
		  	$formatted_date_sql = " CONCAT( monthname(contact_a.birth_date) , ' ',  day(contact_a.birth_date)) as birth_month_and_day ";
		  
		  }else{
		  	print "<br>Configuration Issue: Unrecognized System date format: ".$tmp_system_date_format;
		  
		  }
    	
    	
    		$tmp_age_sql = "((date_format(now(),'%Y') - date_format(contact_a.birth_date,'%Y')) - (date_format(now(),'00-%m-%d') < date_format(contact_a.birth_date,'00-%m-%d'))) AS age "; 
		$select = "DISTINCT contact_a.id as contact_id, ".$formatted_date_sql." ,
		date_format(contact_a.birth_date, '%m-%d' ) as birth_month_and_day_sortable, 
		 contact_a.sort_name as sort_name, $tmp_age_sql , year(contact_a.birth_date) as birth_year, 'birthday' as oc_type,
		 religious_details.".$bmitzvah_date_actual." as bmitzvah_month_and_day " ;

	}
	
	$from  = $this->from( );
 	$where = $this->where( $includeContactIDs ) ; 

	//$days_after_today = ($date_range_start_tmp + $date_range_end_tmp);
	//echo "<!--  date_range: " . $date_range . " -->";
        $sql = "SELECT $select
		FROM  $from
		WHERE $where ";
//order by month(birth_date), oc_day";
	
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
              $sql .=   "ORDER BY month(birth_date), day(birth_date)";
          }
      }

  	if ( $rowcount > 0 && $offset >= 0 ) {
            $sql .= " LIMIT $offset, $rowcount ";
        }

	// print "<br>SQL: ".$sql;

        return $sql;
    }
    
  function from(){
  
  	$tmp_from = ""; 
  	$tmp_group_join = "";
  	if(count( $this->_formValues['group_of_contact'] ) > 0 ){
  		$tmp_group_join = "LEFT JOIN civicrm_group_contact as groups on contact_a.id = groups.contact_id"; 
  	
  	
  	}
  	
  	
  	$tmp_mem_join = "";
  	if( count( $this->_formValues['membership_type_of_contact'] ) > 0 || count( $this->_formValues['membership_org_of_contact'] ) > 0     ){
  		$tmp_mem_join = "LEFT JOIN civicrm_membership as memberships on contact_a.id = memberships.contact_id
	 	LEFT JOIN civicrm_membership_status as mem_status on memberships.status_id = mem_status.id
	 	LEFT JOIN civicrm_membership_type mt ON memberships.membership_type_id = mt.id ";
  	
  	}
  
  	$tmp_from = " civicrm_contact contact_a 
  		  $tmp_group_join 
  		  $tmp_mem_join"; 
  		  	
	return $tmp_from ; 
}
 
  function where($includeContactIDs = false){ 

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
	$tmp_sql_list = $searchTools->getSQLStringFromArray($groups_of_individual);
	

	if(strlen($tmp_sql_list) > 0 ){
		$clauses[] = "groups.group_id IN (".$tmp_sql_list.")" ;
		$clauses[] = "groups.status = 'Added'";
	
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
	
	
	if( ($oc_month_start <> '' ) && is_numeric ($oc_month_start)){
		$clauses[] =  "month(birth_date) >= ".$oc_month_start ;
	}


	if( ($oc_month_end <> '' ) && is_numeric ($oc_month_end)){
		$clauses[]  = "month(birth_date) <= ".$oc_month_end;
	}



	if( ( $oc_day_start <> '') && is_numeric($oc_day_start) ){
		$clauses[] =  "day(birth_date) >= ".$oc_day_start;

	}

	if( ( $oc_day_end <> '') && is_numeric($oc_day_end) ){
		$clauses[] = "day(birth_date) <= ".$oc_day_end;

	}

	$clauses[] =  "birth_date IS NOT NULL";

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
