<?php

/*
 +--------------------------------------------------------------------+
*/

/**
 *
 * @package CRM
 * @copyright Pogstone Inc. (c) 2010-2015
 * $Id$
 *
 */

require_once 'CRM/Report/Form.php';
require_once 'CRM/Contribute/PseudoConstant.php';

class CRM_Report_Form_Contribute_FinancialSummary extends CRM_Report_Form {
    protected $_addressField = false;

    protected $_charts = array( ''         => 'Tabular',
                                'barChart' => 'Bar Chart',
                                'pieChart' => 'Pie Chart'
                                );
   protected $_customGroupExtends = array( );
   protected $_customGroupGroupBy = true;
    
    function __construct( ) {
        $this->_columns = 
         
            array( 'civicrm_contact'  =>
                   array( 'dao'       => 'CRM_Contact_DAO_Contact',
                          'fields'    =>
                          array( 'display_name'      => 
                                 array( 'title'      => ts( 'Contact Name' ),
                                        'no_repeat'  => true ),
                                 'postal_greeting_display' =>
                                 array( 'title'      => ts( 'Postal Greeting' ) ),
                                 'id'           => 
                                 array( 'no_display' => true,
                                        'required'  => true, ), ),
                          'grouping'  => 'contact-fields',
                          'group_bys' => 
                          array( 'id'                =>
                                 array( 'title'      => ts( 'Contact ID' ) ),
                                 'display_name'      => 
                                 array( 'title'      => ts( 'Contact Name' ), ), ),
                          ),
                   
                   'civicrm_email'   =>
                   array( 'dao'       => 'CRM_Core_DAO_Email',
                          'fields'    =>
                          array( 'email' => 
                                 array( 'title'      => ts( 'Email' ),
                                        'no_repeat'  => true ),  ),
                          'grouping'      => 'contact-fields',
                          ),
                   
                   'civicrm_phone'   =>
                   array( 'dao'       => 'CRM_Core_DAO_Phone',
                          'fields'    =>
                          array( 'phone' => 
                                 array( 'title'      => ts( 'Phone' ),
                                        'no_repeat'  => true ), ),
                          'grouping'      => 'contact-fields',
                          ),
                   
                   'civicrm_address' =>
                   array( 'dao' => 'CRM_Core_DAO_Address',
                          'fields' =>
                          array( 'street_address'    => null,
                                 'city'              => null,
                                 'postal_code'       => null,
                                 'state_province_id' => 
                                 array( 'title'   => ts( 'State/Province' ), ),
                                 'country_id'        => 
                                 array( 'title'   => ts( 'Country' ) ), ),
                          'group_bys' =>
                          array( 'street_address'    => null,
                                 'city'              => null,
                                 'postal_code'       => null,
                                 'state_province_id' => 
                                 array( 'title'   => ts( 'State/Province' ), ),
                                 'country_id'        => 
                                 array( 'title'   => ts( 'Country' ), ), ),
                          'grouping'=> 'contact-fields',
                          'filters' =>             
                          array( 'country_id' => 
                                 array( 'title'         => ts( 'Country' ), 
                                        'operatorType'  => CRM_Report_Form::OP_MULTISELECT,
                                        'options'       => CRM_Core_PseudoConstant::country( ),), 
                                 'state_province_id' => 
                                 array( 'title'         => ts( 'State/Province' ), 
                                        'operatorType'  => CRM_Report_Form::OP_MULTISELECT,
                                        'options'       => CRM_Core_PseudoConstant::stateProvince( ),), ),
                          ),

                   'civicrm_financial_type' =>
                   array( 'dao'           => 'CRM_Financial_DAO_FinancialType',
                          'fields'        =>
                          array( 'financial_type'   => null ), 
                          'grouping'      => 'contri-fields',
                          'group_bys'     =>
                          array( 'financial_type'   => null, ), ),

                   'civicrm_contribution' =>
                   array( 'dao'           => 'CRM_Contribute_DAO_Contribution',
                          //'bao'           => 'CRM_Contribute_BAO_Contribution',
                          'fields'        =>
                          array( 'contribution_source' => null, 
                                 'total_amount'        => 
                                 array( 'title'        => ts( 'Amount Statistics' ),
                                        'default'      => true,
                                        'required'     => true,
                                        'statistics'   => 
                                        array('sum'    => ts( 'Aggregate Amount' ), 
                                              'count'  => ts( 'Donations' ), 
                                              'avg'    => ts( 'Average' ), ), ), ),
                          'grouping'              => 'contri-fields',
                          'filters'               =>             
                          array( 'receive_date'   => 
                                 array( 'operatorType' => CRM_Report_Form::OP_DATE ),

                                 'contribution_status_id' => 
                                 array( 'title'        => ts( 'Donation Status' ), 
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'options'      => CRM_Contribute_PseudoConstant::contributionStatus( )                                 
                                        ), 

                                'financial_type_id'   =>
                                   array( 'title'        => ts( 'Financial Type' ), 
                                          'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                          'options'      => CRM_Contribute_PseudoConstant::financialType( )
                                        ),

                                 'total_amount'   => 
                                 array( 'title'   => ts( 'Donation Amount' ), ), 

                                 'total_sum'    => 
                                 array( 'title'   => ts( 'Aggregate Amount' ),
                                        'type'    => CRM_Report_Form::OP_INT,
                                        'dbAlias' => 'civicrm_contribution_total_amount_sum',
                                        'having'  => true ), 

                                 'total_count'    => 
                                 array( 'title'   => ts( 'Donation Count' ),
                                        'type'    => CRM_Report_Form::OP_INT,
                                        'dbAlias' => 'civicrm_contribution_total_amount_count',
                                        'having'  => true ), 
                                 'total_avg'    => 
                                 array( 'title'   => ts( 'Average' ),
                                        'type'    => CRM_Report_Form::OP_INT,
                                        'dbAlias' => 'civicrm_contribution_total_amount_avg',
                                        'having'  => true ), ),
                          'group_bys'           =>
                          array( 'receive_date' => 
                                 array( 'frequency'  => true,
                                        'default'    => true,
                                        'chart'      => true ),
                                 'contribution_source'     => null, ), ),

                   'civicrm_group' => 
                   array( 'dao'    => 'CRM_Contact_DAO_GroupContact',
                          'alias'  => 'cgroup',
                          'filters' =>             
                          array( 'gid' => 
                                 array( 'name'          => 'group_id',
                                        'title'         => ts( 'Group' ),
                                        'operatorType' => CRM_Report_Form::OP_MULTISELECT,
                                        'group'         => true,
                                        'options'       => CRM_Core_PseudoConstant::group( ) ), ), ),
                   
                   );
                   
                   
      
        $this->_tagFilter = true;
        $this->_title = "Projected Income Report";
        // $this->_complexSql = true;
        parent::__construct( );
    }

    function preProcess( ) {
        parent::preProcess( );
    }
    
    function setDefaultValues( $freeze = true ) {
        return parent::setDefaultValues( $freeze );
    }


    function build_recurring_payments_temp_table( $end_date_parm){
  
    
    	$temp_table_name = "civicrm_pogstone_recurring_contribution"; 
    
    	$sql_create_table = "create TEMPORARY table IF NOT EXISTS ".$temp_table_name." like civicrm_contribution";
        $sql_truncate_table = "truncate ".$temp_table_name; 
    
    	$dao_a =& CRM_Core_DAO::executeQuery( $sql_create_table,   CRM_Core_DAO::$_nullArray ) ;
	
	$dao_a->free();
	
	$dao_b =& CRM_Core_DAO::executeQuery( $sql_truncate_table,   CRM_Core_DAO::$_nullArray ) ;
	$dao_b->free();
	
    
   	$sql_str = "select  t1.recur_id, t1.amount, t1.currency, t1.installments, t1.frequency_unit, t1.frequency_interval, 
   	t1.start_date, t1.financial_type_id, t1.contact_id, t2.num_completed_payments , t1.currency
   	 from (SELECT r.id as recur_id, r.amount, r.currency, r.installments, r.frequency_unit, r.frequency_interval, 
   	date( min(c.receive_date)) as start_date, c.financial_type_id, c.contact_id 
   	FROM `civicrm_contribution_recur` r join civicrm_contribution c on 
	r.id = c.contribution_recur_id
	where (r.installments is not null ) 
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
	(t2.num_completed_payments is null  ||  t1.installments > t2.num_completed_payments) " ;

		      
   	$dao =& CRM_Core_DAO::executeQuery( $sql_str,   CRM_Core_DAO::$_nullArray ) ;
    
     	$tmp_display_name = "";
    	while ( $dao->fetch() ) {
      		$tmp_recur_id = $dao->recur_id; 
      		$tmp_recur_amount = $dao->amount;
      		$tmp_installments = $dao->installments; 
      		$tmp_frequency_unit = $dao->frequency_unit;
      		$tmp_frequency_interval = $dao->frequency_interval;
      		$tmp_start_date = $dao->start_date; 
      		$tmp_contribution_type_id = $dao->financial_type_id; 
      		$tmp_contact_id = $dao->contact_id; 
      		$tmp_completed_payments = $dao->num_completed_payments;
      		$tmp_currency = $dao->currency;
      		
      		//print "<br>recur id: ".$tmp_recur_id." Completed payments: ".$tmp_completed_payments;
      		// TODO: Get number of completed contributions 
      		
      		// Insert appropriate records into temp table
      		for($i =0 ; $i < $tmp_installments; $i++){
      		    // Do date arithmetic. 	
      		    //$tmp_installment_date = "'".$tmp_start_date."'";
      		  
      		    if( $i > $tmp_completed_payments -1){
      		        $interval_num = $i * $tmp_frequency_interval;
      		        $tmp_installment_date = "'$tmp_start_date' + INTERVAL $interval_num $tmp_frequency_unit";  
      		    
      		    
      		    	
      		        $cur_installment_to_insert = "INSERT INTO ".$temp_table_name." (contribution_recur_id, contact_id, total_amount, financial_type_id, receive_date, currency   ) 
      		        values ($tmp_recur_id, $tmp_contact_id, $tmp_recur_amount, $tmp_contribution_type_id, $tmp_installment_date, '$tmp_currency'   ) "; 
      		    
      		    //    print "<BR>new row: ".$cur_installment_to_insert ; 
      		    
      		        $dao_insert =& CRM_Core_DAO::executeQuery( $cur_installment_to_insert,   CRM_Core_DAO::$_nullArray ) ;
      		        
      		        }
      		
      		}
      		
      
    	}
    	$dao->free( ); 
        

   
   	return $tmp_table_name; 
   }
	

    function buildQuery( ){
    
      $end_date_parm  = $this->_params['receive_date_to'] ;
     // print "<br>End date: ".$end_date_parm ; 
      if(strlen( $end_date_parm ) > 0 ){
        list( $imonth, $iday, $iyear) = split('/', $end_date_parm); 
     //   print "<br>mm: ".$imonth." day: ".$iday." year: ".$iyear ; 
        $end_date_parm = $iyear.'-'.$imonth.'-'.$iday; 
      
      }
      
     // print "<br>End date: ".$end_date_parm ; 
      
  //    [receive_date_relative] => 0 [receive_date_from] => [receive_date_to]
   //   print_r( $this->_params );
   $tmp_contrib_where = '';	
   $tmp_pledge_pay_where = '';
   if(strlen($end_date_parm) > 0 ){
      $tmp_contrib_where = " AND DATE(contrib.receive_date) < '".$end_date_parm."'";
      $tmp_pledge_pay_where = " and DATE(pp.scheduled_date) < '".$end_date_parm."'";
      $tmp_recur_where = " DATE(receive_date) < '".$end_date_parm."'";
      $tmp_select_field = "'".$end_date_parm."'" ; 
   
   }else{
       $tmp_contrib_where = " AND DATE(contrib.receive_date) <= now()";	
       $tmp_pledge_pay_where = " and DATE(pp.scheduled_date) <=  now()";
       $tmp_recur_where = " DATE(receive_date) <=  now()";
   	 $tmp_select_field = "DATE(now())"; 	
   
   }


   $recur_temp_table_name = self::build_recurring_payments_temp_table( $end_date_parm);
   
  $sql  = "Select ".$tmp_select_field." as date_parm , sum(total_amount) as total_amount, currency, ct.name as financial_type_name, 
   count(*) as count_rows
   from ((SELECT contrib.total_amount, 
   	contrib.receipt_date, contrib.currency, contrib.source, val.label, financial_type_id
	FROM civicrm_contribution contrib,
	civicrm_option_value val, 
	civicrm_option_group grp
	WHERE 
	contrib.contribution_status_id = val.value
	AND  val.option_group_id = grp.id 
	AND grp.name = 'contribution_status'
	and contrib.contribution_status_id = val.value
	and val.name not in ('Completed', 'Cancelled' )  
	and contrib.contribution_recur_id is null".$tmp_contrib_where.
	" and contrib.is_test = 0 )
	UNION ALL
	( SELECT   pp.scheduled_amount as total_amount,
pp.scheduled_date as date, p.currency as currency, 'pledge' as source, val.label as label, financial_type_id
FROM  `civicrm_pledge` AS p, civicrm_pledge_payment as pp,
civicrm_option_value  val, 
civicrm_option_group grp
WHERE p.id = pp.pledge_id
and val.name in ('Overdue', 'Pending' )".
$tmp_pledge_pay_where.
" and pp.status_id = val.value
AND  val.option_group_id = grp.id 
AND grp.name = 'contribution_status'
and p.is_test = 0
	order by 1 )
	UNION ALL
	(SELECT contrib.total_amount, 
   	contrib.receive_date, contrib.currency, contrib.source, '' as label, financial_type_id
	FROM civicrm_pogstone_recurring_contribution as contrib
	WHERE ".$tmp_recur_where."
	)
	) as t1,
	civicrm_financial_type as ct
	where t1.financial_type_id = ct.id
	group by financial_type_id, currency 
	order by financial_type_name";
	
	
	// print "<br>sql: ".$sql ; 
	
    
  /*  
    
    	$sql = "SELECT contrib_type,  acct_code, sum( total_amount) as total_amount,count(total_amount) as count_rows, 
 month( obl_date ) as mm_date, year(obl_date ) as yyyy_date FROM 
(SELECT  ct.name as contrib_type, ct.accounting_code as acct_code,  p.amount AS total_amount,
   p.start_date as obl_date, month( p.start_date ) as mm_date, year(p.start_date ) as yyyy_date 
FROM  `civicrm_pledge` AS p, civicrm_contribution_type AS ct
WHERE p.contribution_type_id = ct.id
AND p.is_test =0
UNION ALL
select ct.name as contrib_type, ct.accounting_code as acct_code, c.total_amount as total_amount , 
 c.receive_date as obl_date, month( c.receive_date ) as mm_date, year(c.receive_date ) as yyyy_date
from civicrm_contribution as c left join civicrm_pledge_payment as pp on c.id = pp.contribution_id
 left join civicrm_contribution_type as ct on c.contribution_type_id = ct.id
where pp.pledge_id is null
and c.is_test =0) AS TBL
GROUP BY yyyy_date, mm_date, contrib_type, acct_code
ORDER BY  yyyy_date, mm_date, contrib_type"; 
    */
    
   
       
        $this->_columnHeaders = array( );
        $this->_columnHeaders['date_parm']['title'] =  'Due by Date';
        $this->_columnHeaders['financial_type_name']['title'] =  'Financial Type';
        
        $this->_columnHeaders['currency']['title'] =  'Currency';
        $this->_columnHeaders['total_amount']['title'] =  'Total Anticipated Amount';
        $this->_columnHeaders['count_rows']['title'] =  'Total Anticipated Transactions';
        
        
                                
                                
                                
       return $sql;
       
    
    
    }
    
    
    
    
    function select( ) {
        $select = array( );
        $this->_columnHeaders = array( );
        foreach ( $this->_columns as $tableName => $table ) {
            if ( array_key_exists('group_bys', $table) ) {
                foreach ( $table['group_bys'] as $fieldName => $field ) {
                    if ( $tableName == 'civicrm_address' ) {
                        $this->_addressField = true;
                    }
                    if ( CRM_Utils_Array::value( $fieldName, $this->_params['group_bys'] ) ) {
                        switch ( CRM_Utils_Array::value( $fieldName, $this->_params['group_bys_freq'] ) ) {
                        case 'YEARWEEK' :
                            $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL WEEKDAY({$field['dbAlias']}) DAY) AS {$tableName}_{$fieldName}_start";
                            $select[] = "YEARWEEK({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "WEEKOFYEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Week';
                            break;
                            
                        case 'YEAR' :
                            $select[] = "MAKEDATE(YEAR({$field['dbAlias']}), 1)  AS {$tableName}_{$fieldName}_start";
                            $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "YEAR({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Year';
                            break;
                            
                        case 'MONTH':
                            $select[] = "DATE_SUB({$field['dbAlias']}, INTERVAL (DAYOFMONTH({$field['dbAlias']})-1) DAY) as {$tableName}_{$fieldName}_start";
                            $select[] = "MONTH({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "MONTHNAME({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Month';
                            break;
                            
                        case 'QUARTER':
                            $select[] = "STR_TO_DATE(CONCAT( 3 * QUARTER( {$field['dbAlias']} ) -2 , '/', '1', '/', YEAR( {$field['dbAlias']} ) ), '%m/%d/%Y') AS {$tableName}_{$fieldName}_start";
                            $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_subtotal";
                            $select[] = "QUARTER({$field['dbAlias']}) AS {$tableName}_{$fieldName}_interval";
                            $field['title'] = 'Quarter';
                            break;
                            
                        }
                        if ( CRM_Utils_Array::value( $fieldName, $this->_params['group_bys_freq'] ) ) {
                            $this->_interval = $field['title'];
                            $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['title'] = 
                                $field['title'] . ' Beginning';
                            $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['type']  = 
                                $field['type'];
                            $this->_columnHeaders["{$tableName}_{$fieldName}_start"]['group_by'] = 
                                $this->_params['group_bys_freq'][$fieldName];

                            // just to make sure these values are transfered to rows.
                            // since we need that for calculation purpose, 
                            // e.g making subtotals look nicer or graphs
                            $this->_columnHeaders["{$tableName}_{$fieldName}_interval"] = array('no_display' => true);
                            $this->_columnHeaders["{$tableName}_{$fieldName}_subtotal"] = array('no_display' => true);
                        }
                    }
                }
            }

            if ( array_key_exists('fields', $table) ) {
                foreach ( $table['fields'] as $fieldName => $field ) {
                    if ( $tableName == 'civicrm_address' ) {
                        $this->_addressField = true;
                    }
                    if ( CRM_Utils_Array::value( 'required', $field ) ||
                         CRM_Utils_Array::value( $fieldName, $this->_params['fields'] ) ) {
                        
                        // only include statistics columns if set
                        if ( CRM_Utils_Array::value('statistics', $field) ) {
                            foreach ( $field['statistics'] as $stat => $label ) {
                                switch (strtolower($stat)) {
                                case 'sum':
                                    $select[] = "SUM({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = $field['type'];
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'count':
                                    $select[] = "COUNT({$field['dbAlias']}) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] = CRM_Utils_Type::T_INT;
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                case 'avg':
                                    $select[] = "ROUND(AVG({$field['dbAlias']}),2) as {$tableName}_{$fieldName}_{$stat}";
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['type'] =  $field['type'];
                                    $this->_columnHeaders["{$tableName}_{$fieldName}_{$stat}"]['title'] = $label;
                                    $this->_statFields[] = "{$tableName}_{$fieldName}_{$stat}";
                                    break;
                                }
                            }   
                            
                        } else {
                            $select[] = "{$field['dbAlias']} as {$tableName}_{$fieldName}";
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['type']  = CRM_Utils_Array::value( 'type', $field );
                            $this->_columnHeaders["{$tableName}_{$fieldName}"]['title'] = CRM_Utils_Array::value( 'title', $field );
                        }
                        
                    }
                }
            }
        }

        $this->_select = "SELECT " . implode( ', ', $select ) . " ";
        
        
    }

    static function formRule( $fields, $files, $self ) {  
        $errors = $grouping = array( );
        //check for searching combination of dispaly columns and
        //grouping criteria
        $ignoreFields = array( 'total_amount', 'display_name' );
        $errors       = $self->customDataFormRule( $fields, $ignoreFields );
        
        if ( CRM_Utils_Array::value( 'receive_date', $fields['group_bys'] ) ) {
            foreach ( $self->_columns as $tableName => $table ) {
                if ( array_key_exists('fields', $table) ) {
                    foreach ( $table['fields'] as $fieldName => $field ) {
                        if ( CRM_Utils_Array::value( $field['name'], $fields['fields'] ) && 
                             $fields['fields'][$field['name']] && 
                             in_array( $field['name'], array( 'display_name', 'postal_greeting_display', 'contribution_source', 'contribution_type' ) ) ) {
                            $grouping[] = $field['title'];
                        }
                    }
                }
            }
            if ( !empty( $grouping ) ) {
                $temp = 'and '. implode(', ', $grouping );
                $errors['fields'] = ts("Please do not use combination of Receive Date %1", array( 1 => $temp ));    
            }
        }
         
        if ( !CRM_Utils_Array::value( 'receive_date', $fields['group_bys'] ) ) {
            if ( CRM_Utils_Array::value( 'receive_date_relative', $fields ) || 
                 CRM_Utils_Date::isDate( $fields['receive_date_from'] ) || 
                 CRM_Utils_Date::isDate( $fields['receive_date_to'] ) ) {
                $errors['receive_date_relative'] = ts("Do not use filter on Date if group by Receive Date is not used ");      
            }
        }         
        if ( !CRM_Utils_Array::value( 'total_amount', $fields['fields'] ) ) {
            foreach ( array( 'total_count_value','total_sum_value','total_avg_value' ) as $val ) {
                if ( CRM_Utils_Array::value( $val, $fields ) ) {
                    $errors[$val] = ts("Please select the Amount Statistics" );      
                }
            }
        }
        
        return $errors;
    }

    function from( ) {
        $this->_from = "
        FROM civicrm_contact  {$this->_aliases['civicrm_contact']}
             INNER JOIN civicrm_contribution   {$this->_aliases['civicrm_contribution']} 
                     ON {$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_contribution']}.contact_id AND
                        {$this->_aliases['civicrm_contribution']}.is_test = 0
             LEFT  JOIN civicrm_financial_type  {$this->_aliases['civicrm_financial_type']} 
                     ON {$this->_aliases['civicrm_contribution']}.financial_type_id ={$this->_aliases['civicrm_financial_type']}.id
             LEFT  JOIN civicrm_email {$this->_aliases['civicrm_email']} 
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_email']}.contact_id AND 
                        {$this->_aliases['civicrm_email']}.is_primary = 1) 
              
             LEFT  JOIN civicrm_phone {$this->_aliases['civicrm_phone']} 
                     ON ({$this->_aliases['civicrm_contact']}.id = {$this->_aliases['civicrm_phone']}.contact_id AND 
                        {$this->_aliases['civicrm_phone']}.is_primary = 1)";

        if ( $this->_addressField ) {
            $this->_from .= "
                  LEFT JOIN civicrm_address {$this->_aliases['civicrm_address']} 
                         ON {$this->_aliases['civicrm_contact']}.id = 
                            {$this->_aliases['civicrm_address']}.contact_id AND 
                            {$this->_aliases['civicrm_address']}.is_primary = 1\n";
        }
    }

    function groupBy( ) {
        $this->_groupBy = "";
        $append = false;
        if ( is_array($this->_params['group_bys']) && 
             !empty($this->_params['group_bys']) ) {
            foreach ( $this->_columns as $tableName => $table ) {
                if ( array_key_exists('group_bys', $table) ) {
                    foreach ( $table['group_bys'] as $fieldName => $field ) {
                        if ( CRM_Utils_Array::value( $fieldName, $this->_params['group_bys'] ) ) {
                            if ( CRM_Utils_Array::value( 'chart', $field ) ) {
                                $this->assign( 'chartSupported', true );
                            }

                            if ( CRM_Utils_Array::value('frequency', $table['group_bys'][$fieldName]) && 
                                 CRM_Utils_Array::value($fieldName, $this->_params['group_bys_freq']) ) {
                                
                                $append = "YEAR({$field['dbAlias']}),";
                                if ( in_array(strtolower($this->_params['group_bys_freq'][$fieldName]), 
                                              array('year')) ) {
                                    $append = '';
                                }
                                $this->_groupBy[] = "$append {$this->_params['group_bys_freq'][$fieldName]}({$field['dbAlias']})";
                                $append = true;
                            } else {
                                $this->_groupBy[] = $field['dbAlias'];
                            }
                        }
                    }
                }
            }
            
            if ( !empty($this->_statFields) && 
                 (( $append && count($this->_groupBy) <= 1 ) || (!$append)) && !$this->_having ) {
                $this->_rollup = " WITH ROLLUP";
            }
            $this->_groupBy = "GROUP BY " . implode( ', ', $this->_groupBy ) . " {$this->_rollup} ";
        } else {
            $this->_groupBy = "GROUP BY {$this->_aliases['civicrm_contact']}.id";
        }
    }

    function statistics( &$rows ) {
        $statistics = parent::statistics( $rows );
  /*
        if ( ! $this->_having ) {
            $select = "
            SELECT COUNT({$this->_aliases['civicrm_contribution']}.total_amount )       as count,
                   SUM({$this->_aliases['civicrm_contribution']}.total_amount )         as amount,
                   ROUND(AVG({$this->_aliases['civicrm_contribution']}.total_amount), 2) as avg
            ";
        
            $sql = "{$select} {$this->_from} {$this->_where}";
            $dao = CRM_Core_DAO::executeQuery( $sql );
        
            if ( $dao->fetch( ) ) {
                $statistics['counts']['amount'] = array( 'value' => $dao->amount,
                                                         'title' => 'Total Amount',
                                                         'type'  => CRM_Utils_Type::T_MONEY );
                $statistics['counts']['count '] = array( 'value' => $dao->count,
                                                         'title' => 'Total Donations' );
                $statistics['counts']['avg   '] = array( 'value' => $dao->avg,
                                                         'title' => 'Average',
                                                         'type'  => CRM_Utils_Type::T_MONEY );
            }
        }
        */
        return $statistics;
    }
    
    function postProcess( ) {
        parent::postProcess( );
    }
    
    function buildChart( &$rows ) {
        $graphRows = array();
        $count = 0;

        if ( CRM_Utils_Array::value('charts', $this->_params ) ) {
            foreach ( $rows as $key => $row ) {
                if ( $row['civicrm_contribution_receive_date_subtotal'] ) {
                    $graphRows['receive_date'][]   = $row['civicrm_contribution_receive_date_start'];
                    $graphRows[$this->_interval][] = $row['civicrm_contribution_receive_date_interval'];
                    $graphRows['value'][]          = $row['civicrm_contribution_total_amount_sum'];
                    $count++;
                }
            }
            
            if ( CRM_Utils_Array::value( 'receive_date', $this->_params['group_bys'] ) ) {
                
                // build the chart.
                require_once 'CRM/Utils/OpenFlashChart.php';
                $config  = CRM_Core_Config::Singleton();
                $graphRows['xname'] = $this->_interval;
                $graphRows['yname'] = "Amount ({$config->defaultCurrency})";
                CRM_Utils_OpenFlashChart::chart( $graphRows, $this->_params['charts'], $this->_interval );
                $this->assign( 'chartType', $this->_params['charts'] );
            }
        }
    }
    
    function alterDisplay( &$rows ) {
        // custom code to alter rows
        $entryFound = false;

        foreach ( $rows as $rowNum => $row ) {
            // make count columns point to detail report
            if ( CRM_Utils_Array::value('receive_date', $this->_params['group_bys'])        && 
                 CRM_Utils_Array::value('civicrm_contribution_receive_date_start',    $row) &&
                 CRM_Utils_Array::value('civicrm_contribution_receive_date_start',    $row) && 
                 CRM_Utils_Array::value('civicrm_contribution_receive_date_subtotal', $row) ) {

                $dateStart = CRM_Utils_Date::customFormat( $row['civicrm_contribution_receive_date_start'], '%Y%m%d' );
                $endDate   = new DateTime( $dateStart );
                $dateEnd   = array( );

                list( $dateEnd['Y'], $dateEnd['M'], $dateEnd['d'] ) = explode(':', $endDate->format('Y:m:d') );

                switch(strtolower($this->_params['group_bys_freq']['receive_date'])) {
                case 'month': 
                    $dateEnd   = date("Ymd", mktime(0, 0, 0, $dateEnd['M']+1, 
                                                    $dateEnd['d']-1, $dateEnd['Y']));
                    break;
                case 'year': 
                    $dateEnd   = date("Ymd", mktime(0, 0, 0, $dateEnd['M'], 
                                                    $dateEnd['d']-1, $dateEnd['Y']+1));
                    break;
                case 'yearweek': 
                    $dateEnd   = date("Ymd", mktime(0, 0, 0, $dateEnd['M'], 
                                                    $dateEnd['d']+6, $dateEnd['Y']));
                    break;
                case 'quarter': 
                    $dateEnd   = date("Ymd", mktime(0, 0, 0, $dateEnd['M']+3, 
                                                    $dateEnd['d']-1, $dateEnd['Y']));
                    break;
                }
                $url =
                    CRM_Report_Utils_Report::getNextUrl( 'contribute/detail',
                                                         "reset=1&force=1&receive_date_from={$dateStart}&receive_date_to={$dateEnd}",
                                                         $this->_absoluteUrl,
                                                         $this->_id
                                                         );
                $rows[$rowNum]['civicrm_contribution_receive_date_start_link'] = $url;
                $rows[$rowNum]['civicrm_contribution_receive_date_start_hover'] = 
                        ts('List all contribution(s) for this date unit.');
                $entryFound = true;
            }

            // make subtotals look nicer
            if ( array_key_exists('civicrm_contribution_receive_date_subtotal', $row) && 
                 !$row['civicrm_contribution_receive_date_subtotal'] ) {
                $this->fixSubTotalDisplay( $rows[$rowNum], $this->_statFields );
                $entryFound = true;
            }

            // handle state province
            if ( array_key_exists('civicrm_address_state_province_id', $row) ) {
                if ( $value = $row['civicrm_address_state_province_id'] ) {
                    $rows[$rowNum]['civicrm_address_state_province_id'] = 
                        CRM_Core_PseudoConstant::stateProvince( $value, false );

                    $url = 
                        CRM_Report_Utils_Report::getNextUrl( 'contribute/detail',
                                                             "reset=1&force=1&state_province_id_op=in&state_province_id_value={$value}", 
                                                             $this->_absoluteUrl, $this->_id );
                    $rows[$rowNum]['civicrm_address_state_province_id_link']  = $url;
                    $rows[$rowNum]['civicrm_address_state_province_id_hover'] = 
                        ts('List all contribution(s) for this state.');
                }
                $entryFound = true;
            }

            // handle country
            if ( array_key_exists('civicrm_address_country_id', $row) ) {
                if ( $value = $row['civicrm_address_country_id'] ) {
                    $rows[$rowNum]['civicrm_address_country_id'] = 
                        CRM_Core_PseudoConstant::country( $value, false );
                    $url = CRM_Report_Utils_Report::getNextUrl( 'contribute/detail',
                                                                "reset=1&force=1&" . 
                                                                "country_id_op=in&country_id_value={$value}",
                                                                $this->_absoluteUrl, $this->_id );
                    $rows[$rowNum]['civicrm_address_country_id_link'] = $url;
                    $rows[$rowNum]['civicrm_address_country_id_hover'] = 
                        ts('List all contribution(s) for this country.');
                }
                
                $entryFound = true;
            }
            
            // convert display name to links
            if ( array_key_exists('civicrm_contact_display_name', $row) && 
                 array_key_exists('civicrm_contact_id', $row) ) {
                $url = CRM_Report_Utils_Report::getNextUrl( 'contribute/detail', 
                                                            'reset=1&force=1&id_op=eq&id_value=' . $row['civicrm_contact_id'],
                                                            $this->_absoluteUrl, $this->_id );
                $rows[$rowNum]['civicrm_contact_display_name_link'] = $url;
                $rows[$rowNum]['civicrm_contact_display_name_hover'] = 
                    ts("Lists detailed contribution(s) for this record.");
                $entryFound = true;
            }

            // skip looking further in rows, if first row itself doesn't 
            // have the column we need
            if ( !$entryFound ) {
                break;
            }
        }
    }
}