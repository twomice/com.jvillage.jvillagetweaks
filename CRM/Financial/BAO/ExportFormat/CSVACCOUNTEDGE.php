<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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
 * @copyright CiviCRM LLC (c) 2004-2016
 */

/**
 * @link http://wiki.civicrm.org/confluence/display/CRM/CiviAccounts+Specifications+-++Batches#CiviAccountsSpecifications-Batches-%C2%A0Overviewofimplementation
 */
class CRM_Financial_BAO_ExportFormat_CSVACCOUNTEDGE extends CRM_Financial_BAO_ExportFormat {

  /**
   * For this phase, we always output these records too so that there isn't data
   * referenced in the journal entries that isn't defined anywhere.
   *
   * Possibly in the future this could be selected by the user.
   */
  public static $complementaryTables = array(
    'ACCNT',
    'CUST',
  );

  /**
   * Class constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * @param array $exportParams
   */
  public function export($exportParams) {
    $export = parent::export($exportParams);

    // Save the file in the public directory.
    $fileName = self::putFile($export);

    foreach (self::$complementaryTables as $rct) {
      $func = "export{$rct}";
      $this->$func();
    }

    // now do general journal entries
    $this->exportTRANS();

    $this->output($fileName);
  }

  /**
   * @param int $batchId
   *
   * @return Object
   */
  public function generateExportQuery($batchId) {
    $do_group_by = true; 
 
    $sql_credit_account_number = "CASE
         WHEN LENGTH( fa_from.accounting_code)  > 0 
         THEN fa_from.accounting_code
         ELSE fac.accounting_code
      END";

    if ($do_group_by) {
      $group_by_sql = " GROUP BY date_format( ft.trxn_date, '%c/%e/%Y' ), fa_to.accounting_code, $sql_credit_account_number ";
      $group_by_select = " sum( ft.total_amount )  AS debit_total_amount, ";
    }
    else {
      $group_by_sql = "";
      $group_by_select = "  ft.total_amount AS debit_total_amount, ";
    }

    $sql = "SELECT
      ft.id as financial_trxn_id,
      date_format( ft.trxn_date, '%c/%e/%Y' ) as trxn_date,
      b.title as batch_title, 
      fa_to.accounting_code AS to_account_code,
      fa_to.name AS to_account_name,
      fa_to.account_type_code AS to_account_type_code,
      $group_by_select
      ft.trxn_id AS trxn_id,
      cov.label AS payment_instrument,
      ft.check_number,
      c.source AS source,
      $sql_credit_account_number as exportable_credit_account_number,   
      ft.currency AS currency,
      cov_status.label AS status,
      CASE
        WHEN efti.entity_id IS NOT NULL
        THEN efti.amount
        ELSE eftc.amount
      END AS amount,
      fa_from.account_type_code AS credit_account_type_code,
      fa_from.accounting_code AS credit_account,
      fa_from.name AS credit_account_name,
      fac.account_type_code AS from_credit_account_type_code,
      fac.accounting_code AS from_credit_account,
      fac.name AS from_credit_account_name,
      fi.description AS item_description
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_financial_account fa_to ON fa_to.id = ft.to_financial_account_id
      LEFT JOIN civicrm_financial_account fa_from ON fa_from.id = ft.from_financial_account_id
      LEFT JOIN civicrm_option_group cog ON cog.name = 'payment_instrument'
      LEFT JOIN civicrm_option_value cov ON (cov.value = ft.payment_instrument_id AND cov.option_group_id = cog.id)
      LEFT JOIN civicrm_entity_financial_trxn eftc ON (eftc.financial_trxn_id  = ft.id AND eftc.entity_table = 'civicrm_contribution')
      LEFT JOIN civicrm_contribution c ON c.id = eftc.entity_id
      LEFT JOIN civicrm_option_group cog_status ON cog_status.name = 'contribution_status'
      LEFT JOIN civicrm_option_value cov_status ON (cov_status.value = ft.status_id AND cov_status.option_group_id = cog_status.id)
      LEFT JOIN civicrm_entity_financial_trxn efti ON (efti.financial_trxn_id  = ft.id AND efti.entity_table = 'civicrm_financial_item')
      LEFT JOIN civicrm_financial_item fi ON fi.id = efti.entity_id
      LEFT JOIN civicrm_financial_account fac ON fac.id = fi.financial_account_id
      LEFT JOIN civicrm_financial_account fa ON fa.id = fi.financial_account_id
      LEFT JOIN civicrm_batch b ON b.id = eb.batch_id 
      WHERE eb.batch_id = ( %1 )
      AND ft.id IS NOT NULL
      $group_by_sql ";

    $params = array(1 => array($batchId, 'String'));
    $dao = CRM_Core_DAO::executeQuery($sql, $params);

    return $dao;
  }

  /**
   * @param $export
   *
   * @return string
   */
  public function putFile($export) {
    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . 'Financial_Transactions_' . $this->_batchIds . '_' . date('YmdHis') . '.' . $this->getFileExtension();
    $this->_downloadFile[] = $config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName));
    $out = fopen($fileName, 'w');
    fputcsv($out, $export['headers']);
    unset($export['headers']);
    if (!empty($export)) {
      foreach ($export as $fields) {
        fputcsv($out, $fields);
      }
      fclose($out);
    }
    return $fileName;
  }

  /**
   * Format table headers.
   *
   * @param array $values
   * @return array
   */
  public function formatHeaders($values) {
    $arrayKeys = array_keys($values);
    $headers = '';
    if (!empty($arrayKeys)) {
      foreach ($values[$arrayKeys[0]] as $title => $value) {
        $headers[] = $title;
      }
    }
    return $headers;
  }

  /**
   * Generate CSV array for export.
   *
   * @param array $export
   */
  public function makeExport($export) {
    foreach ($export as $batchId => $dao) {
      $financialItems = array();
      $this->_batchIds = $batchId;
      while ($dao->fetch()) {
        $creditAccountName = $creditAccountType =
          $creditAccount = NULL;
        if ($dao->credit_account) {
         // $creditAccountName = $dao->credit_account_name;
         //  $creditAccountType = $dao->credit_account_type_code;
          $creditAccount = $dao->credit_account;
        }
        else {
         // $creditAccountName = $dao->from_credit_account_name;
         // $creditAccountType = $dao->from_credit_account_type_code;
          $creditAccount = $dao->from_credit_account;
        }


      // Get batch name 
        $batch_title_tmp = $dao->batch_title;
        $debitAccount =  $dao->to_account_code; 
        $date_to_export = $dao->trxn_date;
        
        $debit_amount_to_export = $dao->debit_total_amount;
        $credit_amount_to_export = $dao->debit_total_amount;
         
    $have_valid_row = false; 
    if( strlen( $date_to_export) > 0 && strlen($debitAccount) > 0 && strlen($creditAccount) > 0 && strlen($debit_amount_to_export) > 0 &&
       strlen($credit_amount_to_export) > 0 ){
       
          $have_valid_row  = true; 
       
       }

       // Create 2 array entries, plus 1 empty array entry. This is because AccountEdge expects 2 data rows per journal entry, plus a spacer row. 
    if( $have_valid_row   ){
// debit side
     $financialItems[] = array(
          'Date' => $date_to_export,
          'Account Number' => $debitAccount ,
          'Debit Amount' =>   $debit_amount_to_export,
          'Credit Amount' =>  '',
          'Memo' => 'Imported from batch: '.$batch_title_tmp,
        );


// credit side
          $financialItems[] = array(
          'Date' => $date_to_export,
          'Account Number' => $creditAccount,
          'Debit Amount' =>  '',
          'Credit Amount' =>  $credit_amount_to_export,
          'Memo' => 'Imported from batch: '.$batch_title_tmp,
        );

// spacer row

 $financialItems[] = array(
          'Date' => '',
          'Account Number' => '',
          'Debit Amount' =>  '',
          'Credit Amount' =>  '',
          'Memo' => '',
        );
        
        
        }

/*
        $financialItems[] = array(
          'Transaction Date' => $dao->trxn_date,
          'Debit Account' => $dao->to_account_code,
          'Debit Account Name' => $dao->to_account_name,
          'Debit Account Type' => $dao->to_account_type_code,
          'Debit Account Amount (Unsplit)' => $dao->debit_total_amount,
          'Transaction ID (Unsplit)' => $dao->trxn_id,
          'Payment Instrument' => $dao->payment_instrument,
          'Check Number' => $dao->check_number,
          'Source' => $dao->source,
          'Currency' => $dao->currency,
          'Transaction Status' => $dao->status,
          'Amount' => $dao->amount,
          'Credit Account' => $creditAccount,
          'Credit Account Name' => $creditAccountName,
          'Credit Account Type' => $creditAccountType,
          'Item Description' => $dao->item_description,
        );
        */
      }
      $financialItems['headers'] = self::formatHeaders($financialItems);
      self::export($financialItems);
    }
    parent::initiateDownload();
  }

  /**
   * @return string
   */
  public function getFileExtension() {
    return 'csv';
  }

  public function exportACCNT() {
  }

  public function exportCUST() {
  }

  public function exportTRANS() {
  }

}