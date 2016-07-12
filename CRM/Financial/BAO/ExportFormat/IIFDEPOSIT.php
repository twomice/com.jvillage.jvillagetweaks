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
class CRM_Financial_BAO_ExportFormat_IIFDEPOSIT extends CRM_Financial_BAO_ExportFormat {

  /**
   * Tab character. Some people's editors replace tabs with spaces so I'm scared to use actual tabs.
   * Can't set it here using chr() because static. Same thing if a const. So it's set in constructor.
   */
  static $SEPARATOR;

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
    self::$SEPARATOR = chr(9);
  }

  /**
   * @param array $exportParams
   */
  public function export($exportParams) {
    parent::export($exportParams);

    foreach (self::$complementaryTables as $rct) {
      $func = "export{$rct}";
      $this->$func();
    }

    // now do general journal entries
    $this->exportTRANS();

    $this->output();
  }

  /**
   * @param null $fileName
   */
  public function output($fileName = NULL) {
    $tplFile = $this->getHookedTemplateFileName();
    $out = self::getTemplate()->fetch($tplFile);
    $fileName = $this->putFile($out);
    self::createActivityExport($this->_batchIds, $fileName);
  }

  /**
   * @param $out
   *
   * @return string
   */
  public function putFile($out) {
    $config = CRM_Core_Config::singleton();
    $fileName = $config->uploadDir . 'Financial_Transactions_' . $this->_batchIds . '_' . date('YmdHis') . '.' . $this->getFileExtension();
    $this->_downloadFile[] = $config->customFileUploadDir . CRM_Utils_File::cleanFileName(basename($fileName));
    $buffer = fopen($fileName, 'w');
    fwrite($buffer, $out);
    fclose($buffer);
    return $fileName;
  }

  /**
   * @param int $batchId
   *
   * @return Object
   */
  public function generateExportQuery($batchId) {

    $sql = "SELECT
      ft.id as financial_trxn_id,
      ft.trxn_date,
      ft.total_amount AS debit_total_amount,
      ft.currency AS currency,
      ft.trxn_id AS trxn_id,
      b.title as batch_title, 
      cov.label AS payment_instrument,
      ft.check_number,
      fa_from.id AS from_account_id,
      fa_from.name AS from_account_name,
      fa_from.accounting_code AS from_account_code,
      fa_from.financial_account_type_id AS from_account_type_id,
      fa_from.description AS from_account_description,
      fa_from.account_type_code AS from_account_type_code,
      fa_to.id AS to_account_id,
      fa_to.name AS to_account_name,
      fa_to.accounting_code AS to_account_code,
      fa_to.financial_account_type_id AS to_account_type_id,
      fa_to.account_type_code AS to_account_type_code,
      fa_to.description AS to_account_description,
      fi.description AS item_description,
      contact_from.id AS contact_from_id,
      contact_from.display_name AS contact_from_name,
      contact_from.first_name AS contact_from_first_name,
      contact_from.last_name AS contact_from_last_name,
       concat ( contact_from.id , '-', contact_from.display_name) as contact_from_for_memo, 
      contact_to.id AS contact_to_id,
      contact_to.display_name AS contact_to_name,
      contact_to.first_name AS contact_to_first_name,
      contact_to.last_name AS contact_to_last_name
      FROM civicrm_entity_batch eb
      LEFT JOIN civicrm_financial_trxn ft ON (eb.entity_id = ft.id AND eb.entity_table = 'civicrm_financial_trxn')
      LEFT JOIN civicrm_financial_account fa_from ON fa_from.id = ft.from_financial_account_id
      LEFT JOIN civicrm_financial_account fa_to ON fa_to.id = ft.to_financial_account_id
      LEFT JOIN civicrm_option_group cog ON cog.name = 'payment_instrument'
      LEFT JOIN civicrm_option_value cov ON (cov.value = ft.payment_instrument_id AND cov.option_group_id = cog.id)
      LEFT JOIN civicrm_contact contact_from ON contact_from.id = fa_from.contact_id
      LEFT JOIN civicrm_contact contact_to ON contact_to.id = fa_to.contact_id
      LEFT JOIN civicrm_entity_financial_trxn efti ON (efti.financial_trxn_id  = ft.id AND efti.entity_table = 'civicrm_financial_item')
      LEFT JOIN civicrm_financial_item fi ON fi.id = efti.entity_id
      LEFT JOIN civicrm_batch b ON b.id = eb.batch_id 
      WHERE eb.batch_id = ( %1 )
      order by ft.payment_instrument_id";

    $params = array(1 => array($batchId, 'String'));
    $dao = CRM_Core_DAO::executeQuery( $sql, $params );

    return $dao;
  }

  /**
   * @param $export
   */
  public function makeExport($export) {
    // Keep running list of accounts and contacts used in this batch, since we need to
    // include those in the output. Only want to include ones used in the batch, not everything in the db,
    // since would increase the chance of messing up user's existing Quickbooks entries.
    foreach ($export as $batchId => $dao) {
      $accounts = $contacts = $journalEntries = $exportParams = array();
      $this->_batchIds = $batchId;
      $date_to_export = ''; 
      while ($dao->fetch()) {
      
        // Get batch name 
        $batch_title_tmp = $dao->batch_title;
        $batch_memo = $batch_title_tmp;
        // Get date to export from batch title
        $tmp_last_part = substr( $batch_title_tmp, -10) ; 
        $date_parts = explode( "-",  $tmp_last_part);
        
        $year = $date_parts[0];
        $month = $date_parts[1];
        $day = $date_parts[2];
        
        //$date_to_export = $month."/".$day."/".$year; 
        // yyyy/mm/dd
        $date_iif = $year."/".$month."/".$day;
        
        // add to running list of accounts
        if (!empty($dao->from_account_id) && !isset($accounts[$dao->from_account_id])) {
          $accounts[$dao->from_account_id] = array(
            'name' => $this->format($dao->from_account_name),
            'account_code' => $this->format($dao->from_account_code),
            'description' => $this->format($dao->from_account_description),
            'type' => $this->format($dao->from_account_type_code),
          );
        }
        if (!empty($dao->to_account_id) && !isset($accounts[$dao->to_account_id])) {
          $accounts[$dao->to_account_id] = array(
            'name' => $this->format($dao->to_account_name),
            'account_code' => $this->format($dao->to_account_code),
            'description' => $this->format($dao->to_account_description),
            'type' => $this->format($dao->to_account_type_code),
          );
        }

        // add to running list of contacts
        if (!empty($dao->contact_from_id) && !isset($contacts[$dao->contact_from_id])) {
          $contacts[$dao->contact_from_id] = array(
            'name' => $this->format($dao->contact_from_name),
            'first_name' => $this->format($dao->contact_from_first_name),
            'last_name' => $this->format($dao->contact_from_last_name),
          );
        }

        if (!empty($dao->contact_to_id) && !isset($contacts[$dao->contact_to_id])) {
          $contacts[$dao->contact_to_id] = array(
            'name' => $this->format($dao->contact_to_name),
            'first_name' => $this->format($dao->contact_to_first_name),
            'last_name' => $this->format($dao->contact_to_last_name),
          );
        }

        // set up the journal entries for this financial trxn
        $journalEntries[$dao->financial_trxn_id] = array(
          'to_account' => array(
            'trxn_date' => $date_iif,
            'trxn_id' => $this->format($dao->trxn_id),
            'account_name' => $this->format($dao->to_account_name),
            'account_code' => $this->format($dao->to_account_code),
            'amount' => $this->format($dao->debit_total_amount, 'money'),
            'contact_name' => $this->format($dao->contact_to_name),
            'payment_instrument' => $this->format($dao->payment_instrument),
            'check_number' => $this->format($dao->check_number),
          ),
          'splits' => array(),
        );

        /*
         * splits has two possibilities depending on FROM account
         */
        if (empty($dao->from_account_id)) {
          // In this case, split records need to use the individual financial_item account for each item in the trxn
          $item_sql = "SELECT
            fa.id AS account_id,
            fa.name AS account_name,
            fa.accounting_code AS account_code,
            fa.description AS account_description,
            fi.description AS description,
            fi.id AS financial_item_id,
            fi.currency AS currency,
            cov.label AS payment_instrument,
            ft.check_number AS check_number,
            fi.transaction_date AS transaction_date,
            fi.amount AS amount,
            fa.account_type_code AS account_type_code,
            contact.id AS contact_id,
            contact.display_name AS contact_name,
            contact.first_name AS contact_first_name,
            contact.last_name AS contact_last_name,
            concat ( contact.id , '-', contact.display_name) as contact_for_memo
            FROM civicrm_entity_financial_trxn eft
            LEFT JOIN civicrm_financial_item fi ON eft.entity_id = fi.id
            LEFT JOIN civicrm_financial_trxn ft ON ft.id = eft.financial_trxn_id
            LEFT JOIN civicrm_option_group cog ON cog.name = 'payment_instrument'
            LEFT JOIN civicrm_option_value cov ON (cov.value = ft.payment_instrument_id AND cov.option_group_id = cog.id)
            LEFT JOIN civicrm_financial_account fa ON fa.id = fi.financial_account_id
            LEFT JOIN civicrm_contact contact ON contact.id = fi.contact_id
            WHERE eft.entity_table = 'civicrm_financial_item'
            AND eft.financial_trxn_id = %1";

          $itemParams = array(1 => array($dao->financial_trxn_id, 'Integer'));

          $itemDAO = CRM_Core_DAO::executeQuery($item_sql, $itemParams);
          while ($itemDAO->fetch()) {
            // add to running list of accounts
            if (!empty($itemDAO->account_id) && !isset($accounts[$itemDAO->account_id])) {
              $accounts[$itemDAO->account_id] = array(
                'name' => $this->format($itemDAO->account_name),
                'account_code' => $this->format($itemDAO->account_code),
                'description' => $this->format($itemDAO->account_description),
                'type' => $this->format($itemDAO->account_type_code),
              );
            }

            if (!empty($itemDAO->contact_id) && !isset($contacts[$itemDAO->contact_id])) {
              $contacts[$itemDAO->contact_id] = array(
                'name' => $this->format($itemDAO->contact_name),
                'first_name' => $this->format($itemDAO->contact_first_name),
                'last_name' => $this->format($itemDAO->contact_last_name),
              );
            }

            // add split line for this item
            $journalEntries[$dao->financial_trxn_id]['splits'][$itemDAO->financial_item_id] = array(
              'trxn_date' => $date_iif,
              'spl_id' => $this->format($itemDAO->financial_item_id),
              'account_name' => $this->format($itemDAO->account_name),
              'account_code' => $this->format($itemDAO->account_code),
              'amount' => '-' .$this->format($itemDAO->amount, 'money'),
              'contact_name' => $this->format($itemDAO->contact_name),
              'payment_instrument' => $this->format($itemDAO->payment_instrument),
              'description' => $this->format($itemDAO->contact_for_memo),
              'check_number' => $this->format($itemDAO->check_number),
              'currency' => $this->format($itemDAO->currency),
            );
          } // end items loop
          $itemDAO->free();
        }
        else {
          // In this case, split record just uses the FROM account from the trxn, and there's only one record here
          $journalEntries[$dao->financial_trxn_id]['splits'][] = array(
            'trxn_date' => $date_iif,
            'spl_id' => $this->format($dao->financial_trxn_id),
            'account_name' => $this->format($dao->from_account_name),
             'account_code' => $this->format($dao->from_account_code),
            'amount' => '-' . $this->format($dao->debit_total_amount, 'money'),
            'contact_name' => $this->format($dao->contact_from_name),
            'description' => $this->format($dao->contact_from_for_memo),
            'payment_instrument' => $this->format($dao->payment_instrument),
            'check_number' => $this->format($dao->check_number),
            'currency' => $this->format($dao->currency),
          );
        }
      }
      
      $prev_id = ""; 
      $prev_deposit = array();
      $cur_deposit = array();
      $all_comb_deposits = array(); 
      
      $prev_asset_account = ""; 
      $cur_asset_account = ""; 
      $cur_split_details = array();
      
      $comb_deposit = array();
      // combine deposits if for the same asset/bank account
      foreach ($journalEntries as $key => $dep){
        $cur_asset_account =   $dep['to_account']['account_code'];
      
      
       if( $cur_asset_account <> $prev_asset_account ){
        
         // to_account.trxn_id   , to_account.trxn_date  ,  to_account.account_code , to_account.amount
         /*
          $journalEntries[$dao->financial_trxn_id] = array(
          'to_account' => array(
            'trxn_date' => $this->format($dao->trxn_date, 'date'),
            'trxn_id' =>  $this->format($dao->trxn_id),
            'account_name' => $this->format($dao->to_account_name),
            'account_code' => $this->format($dao->to_account_code),
            'amount' => $this->format($dao->debit_total_amount, 'money'),
            'contact_name' => $this->format($dao->contact_to_name),
            'payment_instrument' => $this->format($dao->payment_instrument),
            'check_number' => $this->format($dao->check_number),
          ),
          'splits' => array(),
        );
        */
        if( strlen($prev_id) > 0 ){
          //   wrap up previous deposit record
          $deposit_amount_formatted = number_format($deposit_amount, 2, '.', '') ; 
          $comb_deposit['to_account']['amount'] = $deposit_amount_formatted; 
         $comb_deposit['splits'] =  $cur_split_details; 
         $all_comb_deposits[$prev_id] = $comb_deposit; 
         }
       
        // start a new deposit record
         $comb_deposit = array();
         $comb_deposit['to_account'] = array( 'account_code' => $cur_asset_account, 'trxn_date' => $date_iif, 'trxn_id' => $key , 'memo' => $batch_memo  );
         $cur_split_details = array();
         $deposit_amount = 0; 
         
       }
       
      $deposit_amount = $deposit_amount + $dep['to_account']['amount']; 
        foreach( $dep['splits'] as $spl_id => $cur_split){
             $cur_split_details[$spl_id] = $cur_split; 
          
        }
      
      $prev_id = $key; 
      $prev_asset_account =  $cur_asset_account ; 
      
      }
      
      if( strlen($prev_id) > 0 ){
          //   wrap up last deposit record
           $deposit_amount_formatted = number_format($deposit_amount, 2, '.', '') ; 
          $comb_deposit['to_account']['amount'] = $deposit_amount_formatted; 
         $comb_deposit['splits'] =  $cur_split_details; 
         $all_comb_deposits[$prev_id] = $comb_deposit; 
         }
      
      /*
      !TRNS{$tabchar}TRNSID{$tabchar}TRNSTYPE{$tabchar}DATE{$tabchar}ACCNT{$tabchar}CLASS{$tabchar}AMOUNT{$tabchar}DOCNUM{$tabchar}MEMO{$tabchar}PAYMETH
!SPL{$tabchar}SPLID{$tabchar}TRNSTYPE{$tabchar}DATE{$tabchar}ACCNT{$tabchar}CLASS{$tabchar}AMOUNT{$tabchar}DOCNUM{$tabchar}MEMO{$tabchar}PAYMETH
!ENDTRNS
{foreach from=$journalEntries key=id item=je}
TRNS{$tabchar}{$je.to_account.trxn_id}{$tabchar}DEPOSIT{$tabchar}{$je.to_account.trxn_date}{$tabchar}{$je.to_account.account_code}{$tabchar}{$tabchar}{$je.to_account.amount}{$tabchar}{$je.to_account.check_number}{$tabchar}{$tabchar}{$je.to_account.payment_instrument}
{foreach from=$je.splits key=spl_id item=spl}
SPL{$tabchar}{$spl.spl_id}{$tabchar}DEPOSIT{$tabchar}{$spl.trxn_date}{$tabchar}{$spl.account_code}{$tabchar}{$tabchar}{$spl.amount}{$tabchar}{$spl.check_number}{$tabchar}{$spl.description}{$tabchar}{$spl.payment_instrument}
{/foreach}
ENDTRNS
{/foreach}

*/

      $exportParams = array(
        'accounts' => $accounts,
        'contacts' => $contacts,
        'journalEntries' => $all_comb_deposits,
      );
      self::export($exportParams);
    }
    parent::initiateDownload();
  }

  public function exportACCNT() {
    self::assign('accounts', $this->_exportParams['accounts']);
  }

  public function exportCUST() {
    self::assign('contacts', $this->_exportParams['contacts']);
  }

  public function exportTRANS() {
    self::assign('journalEntries', $this->_exportParams['journalEntries']);
  }

  /**
   * @return string
   */
  public function getMimeType() {
    return 'application/octet-stream';
  }

  /**
   * @return string
   */
  public function getFileExtension() {
    return 'iif';
  }

  /**
   * @return string
   */
  public function getHookedTemplateFileName() {
    return 'CRM/Financial/ExportFormat/IIFDEPOSIT.tpl';
  }

  /**
   * @param string $s
   *   the input string
   * @param string $type
   *   type can be string, date, or notepad
   *
   * @return bool|mixed|string
   */
  public static function format($s, $type = 'string') {
    // If I remember right there's a couple things:
    // NOTEPAD field needs to be surrounded by quotes and then get rid of double quotes inside, also newlines should be literal \n, and ditch any ascii 0x0d's.
    // Date handling has changed over the years. It used to only understand mm/dd/yy but I think now it might depend on your OS settings. Sometimes mm/dd/yyyy works but sometimes it wants yyyy/mm/dd, at least where I had used it.
    // In all cases need to do something with tabs in the input.

    $s1 = str_replace(self::$SEPARATOR, '\t', $s);
    switch($type) {
      case 'date':
        $sout = date('Y/m/d', strtotime($s1));
        break;

      case 'money':
        $sout = CRM_Utils_Money::format($s, NULL, NULL, TRUE);
        break;

      case 'string':
      case 'notepad':
        $s2 = str_replace("\n", '\n', $s1);
        $s3 = str_replace("\r", '', $s2);
        $s4 = str_replace('"', "'", $s3);
        if ($type == 'notepad') {
          $sout = '"' . $s4 . '"';
        }
        else {
          $sout = $s4;
        }
        break;
    }

    return $sout;
  }

}
