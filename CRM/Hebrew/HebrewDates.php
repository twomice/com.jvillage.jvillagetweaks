<?php

class HebrewCalendar {

  // Put Purim in Adar II, if its not a leap year it should be shifted to Adar. 
  // yom hazikaron needs to be shifted if it or the day after fall on a shabbat. 
  // yom haatzmaut needs to be shifted if it or the day before fall on a shabbat. 
  private $jewish_holidays_major = array(
    "rosh_hashana" => "1/1",
    "rosh_hashana_2" => "1/2",
    "yom_kippur" => "1/10",
    "sukkot" => "1/15",
    "shemini_atzeret" => "1/22",
    "simchat_torah" => "1/23",
    "hannukah_1" => "3/24",
    "hannukah_2" => "3/25",
    "hannukah_3" => "3/26",
    "hannukah_4" => "3/27",
    "hannukah_5" => "3/28",
    "hannukah_6" => "3/29",
    "hannukah_7" => "3/30",
    "hannukah_8" => "4/1",
    "purim" => "7/14",
    "passover" => "8/15",
    "shavuot" => "10/6",
    "tisha_b_av" => "12/9",
    "yom_ha_shoah" => "8/27",
    "yom_hazikaron" => "9/4",
    "yom_haatzmaut" => "9/5",
    "yom_yerushalayim" => "9/28",
  );
  private $jewish_holidays_minor = array('aaa' => 'a');

  // Should always be set to false in a production environment.
  const ALWAYS_CLEAR_TEMP_TABLE = false;

  // After how many minutes should yahrzeit cache data be recalculated?
  // In a production environment this is typically 12 hours, ie 720 minutes. 
  const YAHRZEIT_CACHE_TIMEOUT = "15";

  const HEBREW_MONTH_TISHREI = "1";
  const HEBREW_MONTH_HESHVAN = "2";
  const HEBREW_MONTH_KISLEV = "3";
  const HEBREW_MONTH_TEVET = "4";
  const HEBREW_MONTH_SHEVAT = "5";
  const HEBREW_MONTH_ADAR = "6";
  const HEBREW_MONTH_ADAR_2 = "7";
  const HEBREW_MONTH_NISAN = "8";
  const HEBREW_MONTH_IYYAR = "9";
  const HEBREW_MONTH_SIVAN = "10";
  const HEBREW_MONTH_TAMUZ = "11";
  const HEBREW_MONTH_AV = "12";
  const HEBREW_MONTH_ELUL = "13";

  /**
   * This function takes in a English date, and returns the name of
   * the Jewish holiday. If there is no holiday, then  an empty
   * string is returned.
   */
  function get_rosh_hodesh_name($iyear, $imonth, $iday) {
    $tmp_name = "";
    $date_before_sunset = 1;
    $hebrew_date_format = 'mm/dd/yy';
    $heb_date = self::util_convert2hebrew_date($iyear, $imonth, $iday, $date_before_sunset, $hebrew_date_format);

    $heb_date_array = explode('/', $heb_date);
    $heb_month = $heb_date_array[0];
    $heb_day = $heb_date_array[1];
    $heb_year = $heb_date_array[2];

    if ($heb_month <> "1") {
      if ($heb_day == "1") {
        $julian_date = gregoriantojd($imonth, $iday, $iyear);
        //$month_name = self::util_get_hebrew_month_name( $julian_date, $heb_date);
        $tmp_name = "Rosh Hodesh " . $month_name;
      } elseif ($heb_day == "30") {
        // TODO: Need to advance Jullian date to the next day. 
        // TODO: Need to advance Hebrew date to the next day. 
        $tmp_name = "Rosh Hodesh " . $month_name;
      } else {
        $tmp_name = "";
      }
    }

    return $tmp_name;
  }

  /**
   * This function takes in a English date, and returns the name of
   * the Jewish holiday. If there is no holiday, then  an empty
   * string is returned.
   */
  function get_jewish_holiday_name($iyear, $imonth, $iday) {

    $date_before_sunset = 1;
    $hebrew_date_format = 'mm/dd/yy';
    $heb_date = self::util_convert2hebrew_date($iyear, $imonth, $iday, $date_before_sunset, $hebrew_date_format);

    $heb_date_array = explode("/", $heb_date);
    $heb_month = $heb_date_array[0];
    $heb_day = $heb_date_array[1];
    $heb_year = $heb_date_array[2];

    $heb_mm_dd = $heb_month . '/' . $heb_day;
    // Do special Purim logic for leap years.
    // When there are 2 Adars, then Purim is celebrated during Adar II.
    if (self::is_hebrew_year_leap_year($heb_year)) {
      $this->jewish_holidays_major['purim'] = "7/14";
    } else {
      $this->jewish_holidays_major['purim'] = "6/14";
    }



    //If the 5th of Iyar falls on a Friday or Saturday, Yom HaAtzmaut is moved up to the preceding Thursday.
    // If the 5th of Iyar is on a Monday,  Yom HaAtzmaut is postponed to Tuesday.
    // Also, Yom HaZikaron is always observed the day before Yom HaAtzmaut.
    $gregorian_date_format = 'dd-mm-yyyy';
    $erev_start_flag = '0';
    $iyar_heb_month = '9';
    $iyar_5_heb_day = '5';

    $iyar_5_gregorian_date_tmp = self::util_convert_hebrew2gregorian_date($heb_year, $iyar_heb_month, $iyar_5_heb_day, $erev_start_flag, $gregorian_date_format);
    $iyar_5_tmp = new DateTime($iyar_5_gregorian_date_tmp);
    $iyar_5_timestamp = $iyar_5_tmp->getTimestamp();
    $iyar_5_day_of_week = date('w', $iyar_5_timestamp);
    if ($iyar_5_day_of_week == '5') {
      // Iyar 5 falls on a Friday, back it up 1 day.
      $this->jewish_holidays_major['yom_haatzmaut'] = "9/4";
      $this->jewish_holidays_major['yom_hazikaron'] = "9/3";
    } else if ($iyar_5_day_of_week == '6') {
      // Iyar 5 falls on a Saturday, back it up 2 days.
      $this->jewish_holidays_major['yom_haatzmaut'] = "9/3";
      $this->jewish_holidays_major['yom_hazikaron'] = "9/2";
    } else if ($iyar_5_day_of_week == '1') {
      // Iyar 5 falls on a Monday, push it out 1 day to Tuesday.
      $this->jewish_holidays_major['yom_haatzmaut'] = "9/6";
      $this->jewish_holidays_major['yom_hazikaron'] = "9/5";
    }

    /*     * ********************************************************************* */
    // At this point, we have the correct adjusted dates for yom_haatzmaut and yom_hazikaron
    // Now we may need to adjust the last 2 nighs of Hannuka, if there is no "Kislev 30" this year.
    $heb_kislev_month = '3';
    $heb_kislev_last_day = '30';

    if (!( self::verify_hebrew_date($heb_year, $heb_kislev_month, $heb_kislev_last_day) )) {
      $this->jewish_holidays_major['hannukah_7'] = "4/1";
      $this->jewish_holidays_major['hannukah_8'] = "4/2";
    }

    // Go ahead and get human-readable names of holidays.
    switch ($heb_mm_dd) {
      case $this->jewish_holidays_major['rosh_hashana'] :
        $holiday_name = "Rosh Hashana";
        break;
      case $this->jewish_holidays_major['rosh_hashana_2'] :
        $holiday_name = "Rosh Hashana II";
        break;
      case $this->jewish_holidays_major['yom_kippur'] :
        $holiday_name = "Yom Kippur";
        break;
      case $this->jewish_holidays_major['sukkot'] :
        $holiday_name = "Sukkot";
        break;
      case $this->jewish_holidays_major['shemini_atzeret'] :
        $holiday_name = "Shemini Atzeret";
        break;
      case $this->jewish_holidays_major['simchat_torah'] :
        $holiday_name = "Simchat Torah";
        break;
      case $this->jewish_holidays_major['hannukah_1'] :
        $holiday_name = "Hannukah 1 Candle";
        break;
      case $this->jewish_holidays_major['hannukah_2'] :
        $holiday_name = "Hannukah 2 Candles";
        break;
      case $this->jewish_holidays_major['hannukah_3'] :
        $holiday_name = "Hannukah 3 Candles";
        break;
      case $this->jewish_holidays_major['hannukah_4'] :
        $holiday_name = "Hannukah 4 Candles";
        break;
      case $this->jewish_holidays_major['hannukah_5'] :
        $holiday_name = "Hannukah 5 Candles";
        break;
      case $this->jewish_holidays_major['hannukah_6'] :
        $holiday_name = "Hannukah 6 Candles";
        break;
      case $this->jewish_holidays_major['hannukah_7'] :
        $holiday_name = "Hannukah 7 Candles";
        break;
      case $this->jewish_holidays_major['hannukah_8'] :
        $holiday_name = "Hannukah 8 Candles";
        break;
      case $this->jewish_holidays_major['purim'] :
        $holiday_name = "Purim";
        break;
      case $this->jewish_holidays_major['passover'] :
        $holiday_name = "Passover";
        break;
      case $this->jewish_holidays_major['shavuot'] :
        $holiday_name = "Shavuot";
        break;
      case $this->jewish_holidays_major['tisha_b_av'] :
        $holiday_name = "Tish'a B'Av";
        break;
      case $this->jewish_holidays_major['yom_ha_shoah'] :
        $holiday_name = "Yom HaShoah";
        break;
      case $this->jewish_holidays_major['yom_hazikaron'] :
        $holiday_name = "Yom HaZikaron";
        break;
      case $this->jewish_holidays_major['yom_haatzmaut'] :
        $holiday_name = "Yom HaAtzmaut";
        break;
      case $this->jewish_holidays_major['yom_yerushalayim'] :
        $holiday_name = "Yom Yerushalayim";
        break;
    }

    return $holiday_name;
  }

  /**
   * This function takes a Hebrew date as input parms: yyyy, mm, dd
   * and returns it nicely formated as: dd HebrewMonthName yyyy
   */
  function util_formatHebrewDate(&$iyear, &$imonth, &$iday) {
    if ($imonth == '') {
      return "Month is required";
    } elseif ($iday == '') {
      return "Day is required";
    } elseif ($iyear == '') {
      return "Year is required";
    } else {
      $julian_date = cal_to_jd(CAL_JEWISH, $imonth, $iday, $iyear);
      $heb_month_name = jdmonthname($julian_date, 4);
      $formated_hebrew_date = "$iday $heb_month_name $iyear";
      return $formated_hebrew_date;
    }
  }

  /**
   * This function takes a English date then returns the sunset time.
   */
  function retrieve_sunset_or_candlelighting_times($iyear, $imonth, $iday, $sunset_or_candle) {

    if (strlen($iyear) < 1) {
      return 'Unknow year, cannot do sunset/candlelighting time';
    }

    $tmp_date = $iyear . '-' . $imonth . '-' . $iday . " 17:00";
    $date = new DateTime($tmp_date);
    $caldate_timestamp = $date->getTimestamp();

    // Check if this is Friday.
    if (date('w', $caldate_timestamp) != '5') {
      return NULL;
    }

    // Get lat/long, zenith, and offset times for primary organization.
    require_once('utils/util_custom_fields.php');
    $customFieldLabels = array();
    $custom_field_zenith_label = "Zenith Used to Calculate Sunset";
    $customFieldLabels[] = $custom_field_zenith_label;
    $custom_field_minutes_offset = "Number of Minutes Offset";
    $customFieldLabels[] = $custom_field_minutes_offset;
    $custom_fields_candle_offset = "Number of Minutes before sundown to light candles";
    $customFieldLabels[] = $custom_fields_candle_offset;
    $outCustomColumnNames = array();
    getCustomTableFieldNames("Calendar Preferences", $customFieldLabels, $sql_table_name, $outCustomColumnNames);
    $sql = "
      Select geo_code_1, geo_code_2, {$outCustomColumnNames[$custom_field_zenith_label]} as zenith,
        {$outCustomColumnNames[$custom_field_minutes_offset]} as minutes_offset,
        {$outCustomColumnNames[$custom_fields_candle_offset]} as candle_offset
        from civicrm_contact AS contact_a
        left join civicrm_address on contact_a.id = civicrm_address.contact_id
        left join civicrm_state_province on civicrm_address.state_province_id = civicrm_state_province.id
        left join $sql_table_name as cal_prefs on contact_a.id = cal_prefs.entity_id
        WHERE
        contact_a.contact_sub_type =  'Primary_Organization' AND
        civicrm_address.is_primary = 1
        order by contact_a.id ";
    $dao = & CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);
    if ($dao->fetch()) {
      $latitude = $dao->geo_code_1;
      $longitude = $dao->geo_code_2;
      $zenith = $dao->zenith ?: 89.2;
      $minutes_offset = $dao->minutes_offset ?: 0;
      $candle_offset = $dao->candle_offset ?: '18';
    } else {
      return "Unknown Location, cannot do candlelighting/sunset time";
    }
    $dao->free();

    // Get sunset time based on lat/long, local timezone, zenith, etc.
    $dateTimeZoneUTC = new DateTimeZone('UTC');
    $local_timezone = new DateTimeZone(date_default_timezone_get());
    $dateTimeUTC = new DateTime($tmp_date, $dateTimeZoneUTC);
    $dateTimeLocal = new DateTime($tmp_date, $local_timezone);
    $tmp_off = timezone_offset_get($local_timezone, $dateTimeUTC);
    $tmp_UTC_offset = round($tmp_off / 3600);
    $sunset_time = date_sunset($caldate_timestamp, SUNFUNCS_RET_TIMESTAMP, $latitude, $longitude, $zenith, $tmp_UTC_offset);
    $sunset_time += ($minutes_offset * 60);

    // Adjust for candles if so requested.
    switch ($sunset_or_candle) {
      case 'sunset':
        $output_time = $sunset_time;
        break;
      case 'candle':
        $output_time = $sunset_time + ($candle_offset * -60);
        break;
      default:
        // This should never happen.
        return NULL;
    }

    return date('g:i a', $output_time);
  }

  /**
   * This function queries info from the CiviCRM database and returns 
   * the calculated Hebrew dates as an array.
   */
  function retrieve_hebrew_demographic_dates(&$cur_id_parm) {
    $record_found = false;

    require_once('utils/util_custom_fields.php');

    $custom_field_group_label = "Extended Date Information";
    $custom_field_birthdate_sunset_label = "Birth Date Before Sunset";
    $custom_field_deathdate_sunset_label = "Death Date Before Sunset";

    $customFieldLabels = array($custom_field_birthdate_sunset_label, $custom_field_deathdate_sunset_label);
    $extended_date_table = "";
    $outCustomColumnNames = array();

    try {
      getCustomTableFieldNames($custom_field_group_label, $customFieldLabels, $extended_date_table, $outCustomColumnNames);

      $extended_birth_date = $outCustomColumnNames[$custom_field_birthdate_sunset_label];
      $extended_death_date = $outCustomColumnNames[$custom_field_deathdate_sunset_label];
    } catch (Exception $e) {
      $rtn_data["hebrew_date_of_birth"] = $e->getMessage();
      return $rtn_data;
    }

// fetch the details about the individual.
    $query = "
   SELECT civicrm_contact.first_name as first_name, 
   year(civicrm_contact.birth_date) as birth_year,
   month(civicrm_contact.birth_date) as birth_month,
   day(civicrm_contact.birth_date) as birth_day,  
   civicrm_contact.is_deceased as is_deceased,
   civicrm_contact.contact_type as contact_type,
   civicrm_option_value.label as gender_label,
   $extended_date_table.$extended_birth_date as birth_date_before_sunset,
   year(civicrm_contact.deceased_date) as deceased_year,
   month(civicrm_contact.deceased_date) as deceased_month,
   day(civicrm_contact.deceased_date) as deceased_day,
   $extended_date_table.$extended_death_date as deceased_date_before_sunset,
   civicrm_option_value.label as gender_label
     FROM civicrm_contact 
       LEFT JOIN $extended_date_table ON civicrm_contact.id = $extended_date_table.entity_id 
       LEFT JOIN civicrm_option_value ON civicrm_option_value.option_group_id = 3 AND civicrm_contact.gender_id = civicrm_option_value.value
    WHERE civicrm_contact.id = %1 AND civicrm_contact.contact_type = 'Individual'  ";




    $p = array(1 => array($cur_id_parm, 'Integer'));
    $dao = & CRM_Core_DAO::executeQuery($query, $p);

    $first_name = null;
    $birth_year = null;
    $birth_month = null;
    $birth_day = null;
    $birth_date_before_sunset = null;
    $is_deceased = null;
    $deceased_year = null;
    $deceased_month = null;
    $deceased_day = null;
    $deceased_date_before_sunset = null;
    $gender = null;


    if ($dao->fetch()) {
      $record_found = true;

      $first_name = $dao->first_name;
      $birth_year = $dao->birth_year;
      $birth_month = $dao->birth_month;
      $birth_day = $dao->birth_day;
      $birth_date_before_sunset = $dao->birth_date_before_sunset;
      $is_deceased = $dao->is_deceased;
      $gender = $dao->gender_label;
      $rtn_data["contact_type"] = $dao->contact_type;



      if ($is_deceased) {
        $rtn_data["is_deceased"] = true;
        $deceased_year = $dao->deceased_year;
        $deceased_month = $dao->deceased_month;
        $deceased_day = $dao->deceased_day;
        $deceased_date_before_sunset = $dao->deceased_date_before_sunset;
      } else {
        $rtn_data["is_deceased"] = false;
      }
    }
    $dao->free();

    if ($record_found) {
      $hebrew_date_format = 'dd MM yy';
      $hebrew_birth_date_formated = self::util_convert2hebrew_date($birth_year, $birth_month, $birth_day, $birth_date_before_sunset, $hebrew_date_format);
      //$screen->assign("hebrew_date_of_birth", $hebrew_birth_date_formated);
      $rtn_data["hebrew_date_of_birth"] = $hebrew_birth_date_formated;

      $heb_date_format = 'hebrew';
      $hebrew_birth_date_formated_as_hebrew = self::util_convert2hebrew_date($birth_year, $birth_month, $birth_day, $birth_date_before_sunset, $heb_date_format);
      //$screen->assign("hebrew_date_of_birth", $hebrew_birth_date_formated);
      $rtn_data["hebrew_date_of_birth_hebrew"] = $hebrew_birth_date_formated_as_hebrew;


      $config = CRM_Core_Config::singleton( );

      $tmp_system_date_format = $config->dateInputFormat;
      if ($tmp_system_date_format == 'dd/mm/yy') {
        $gregorian_date_format = "dd MM yyyy";
      } else if ($tmp_system_date_format == 'mm/dd/yy') {
        $gregorian_date_format = "MM dd, yyyy";
      } else {
        print "<br>Configuration Issue: Unrecognized System date format: " . $tmp_system_date_format;
      }




      if ($is_deceased) {
        $hebrew_death_date_formated = self::util_convert2hebrew_date($deceased_year, $deceased_month, $deceased_day, $deceased_date_before_sunset, $hebrew_date_format);
        //$screen->assign("hebrew_date_of_death" , $hebrew_death_date_formated );
        $rtn_data["hebrew_date_of_death"] = $hebrew_death_date_formated;
        $erev_start_flag = '1';
        // $gregorian_date_format = "MM dd, yyyy";
        $yahrzeit_date_formated = self::util_get_next_yahrzeit_date($deceased_year, $deceased_month, $deceased_day, $deceased_date_before_sunset, $erev_start_flag, $gregorian_date_format);
        //$screen->assign("yahrzeit_date", $yahrzeit_date_formated);
        $rtn_data["yahrzeit_date_observe_hebrew"] = $yahrzeit_date_formated;
        // Make sure we get the next English yahrzeit date.
        $next_flag = 'next';
        $rtn_data["yahrzeit_date_observe_english"] = self::getYahrzeitDateEnglishObservanceFormated($deceased_year, $deceased_month, $deceased_day, $next_flag);
      } else {

        if ($hebrew_birth_date_formated == "Cannot determine Hebrew date") {
          # TODO: Need better error handling.
          return $rtn_data;
        }

        $erev_start_flag = '1';
        $gregorian_date_format = "MM dd, yyyy";
        if ($gender == 'Male') {
          $bar_bat_mitzvah_flag = "bar";
          $bar_bat_label = "Bar Mitzvah";
        } else if ($gender == 'Female') {
          $bar_bat_mitzvah_flag = "bat";
          $bar_bat_label = "Bat Mitzvah";
        } else {
          //$screen->assign("bar_bat_mitzvah_label" , "bar/bat mitzvah");
          //$screen->assign("earliest_bar_bat_mitzvah_date",  " Cannot determine Bar/Bat Mitzvah date due to unrecognized gender." );
          $rtn_data["bar_bat_mitzvah_label"] = "bar/bat mitzvah";
          $rtn_data["earliest_bar_bat_mitzvah_date"] = " Cannot determine Bar/Bat Mitzvah date due to unrecognized gender.";
          return $rtn_data;
        }

        $bat_mitzvah_date_formated = self::util_get_bar_bat_mizvah_date($birth_year, $birth_month, $birth_day, $birth_date_before_sunset, $erev_start_flag, $bar_bat_mitzvah_flag, $gregorian_date_format);
        //$screen->assign("bar_bat_mitzvah_label" , $bar_bat_label);
        //$screen->assign("earliest_bar_bat_mitzvah_date", $bat_mitzvah_date_formated);
        $rtn_data["bar_bat_mitzvah_label"] = $bar_bat_label;
        $rtn_data["earliest_bar_bat_mitzvah_date"] = $bat_mitzvah_date_formated;
      }
    } // end if record found.
    return $rtn_data;
  }

// end of function

  /**
   * Adjust tokens arrays to a standardized structure. CiviCRM will pass tokens
   * as array('token_name' => 1) in CiviMail, but as array('token_name') for
   * other mailings.
   *
   * @param Array $tokens The tokens as passed to, e.g., hook_civicrm_tokenValues.
   * @return Array
   */
  function normalize_tokens($tokens) {
    $normalized_tokens = array();
    foreach ($tokens as $group => $group_tokens) {
      if (current($group_tokens) == 1) {
        $normalized_tokens[$group] = array_keys($group_tokens);
      } else {
        $normalized_tokens[$group] = $group_tokens;
      }
    }
    return $normalized_tokens;
  }

  /**
   * Prepare mail-merge tokens related to yahrzeits.
   */
  function process_yahrzeit_tokens(&$values, &$contactIDs, $tokens = array(), $token_strings = array()) {
    $tokens = self::normalize_tokens($tokens);

    //  yahrzeit_morning_format_english

    $default_parm = "";



    /*     * *  TODO: Get date choice from session ** */
    $english_start_date = '2011-10-01';
    $english_end_date = '2011-10-15';



    session_start();

    require_once 'CRM/Hebrew/HebrewDates.php';
    require_once('utils/util_custom_fields.php');

    // Process "yahrzeit.all" token if in use.
    if (in_array(preg_replace('/^yahrzeit\./', '', $token_strings['all']), $tokens['yahrzeit'])) {

      $yizkor_sql_str = "SELECT DISTINCT mourner_contact_id as contact_id, mourner_contact_id as id, mourner_name as sort_name, deceased_name as deceased_name,
      deceased_display_name as deceased_display_name, deceased_contact_id, mourner_email as email , contact_b.deceased_date,
      contact_b.deceased_date as ddate,
      d_before_sunset, hebrew_deceased_date,
       concat( year(yahrzeit_date), '-', month(yahrzeit_date), '-', day(yahrzeit_date)) as yahrzeit_date_sort , yahrzeit_date_display, relationship_name_formatted,
        yahrzeit_type, mourner_observance_preference
         FROM " . self::get_sql_table_name() . " contact_b INNER JOIN civicrm_contact contact_a ON contact_a.id = contact_b.mourner_contact_id
         WHERE contact_b.created_date >= DATE_SUB(CURDATE(), INTERVAL 10 MINUTE) AND (yahrzeit_type = mourner_observance_preference)
         AND yahrzeit_date >= CURDATE()
         AND contact_b.mourner_contact_id in (   " . implode(',', $contactIDs) . " )
         ORDER BY sort_name asc";

      $html_table_begin = '<table border=0 style="border-spacing: 0; border-collapse: collapse; width: 100%">
        <tr><th style="text-align: left;">In memory of</th>
        <th style="text-align: left;">Hebrew Date of Death</th>
        <th style="text-align: left;">English Date of death</th>
        <th style="text-align: left;">Next Yahrzeit</th>
        <th style="text-align: left;">Relationship</th>
        <th style="text-align: left;">Observance Type</th>
        </tr>';
      $html_table_end = ' </table>  	 ';
      $prev_cid = "";
      $cur_cid_html = "";
      $dao = & CRM_Core_DAO::executeQuery($yizkor_sql_str, CRM_Core_DAO::$_nullArray);

      while ($dao->fetch()) {
        //  print "<br>Have yizkor record!";
        $cur_cid = $dao->contact_id;

        if ($cur_cid != $prev_cid) {
          if ($prev_cid != "") {
            // Wrap up table for previous contact.
            $cur_cid_html = $cur_cid_html . $html_table_end;
            $values[$prev_cid][$token_strings['all']] = $values[$prev_cid][$token_strings['short']] = $cur_cid_html;
          }

          // start html table for this contact
          $cur_cid_html = $html_table_begin;
        }

        //   figure out the next yahrzeit for each record,
        $deceased_name = $dao->deceased_name;
        $mourner_contact_id = $dao->contact_id;
        $mourner_email = $dao->email;
        $mourner_name = $dao->sort_name;
        //$deceased_year = $dao->dyear;
        //$deceased_month = $dao->dmonth;
        //$deceased_day = $dao->dday;
        $hebrew_deceased_date = $dao->hebrew_deceased_date;
        $english_deceased_date = $dao->ddate;
        $deceased_date_before_sunset = $dao->d_before_sunset;
        $relationship_to_mourner = $dao->relationship_name_formatted;
        $yahrzeit_date_display = $dao->yahrzeit_date_display;
        $yahrzeit_type = $dao->yahrzeit_type;

        if ($yahrzeit_type == '1') {
          $yahrzeit_type_formatted = 'English';
        } else {
          $yahrzeit_type_formatted = 'Hebrew';
        }



        $yahrzeit_html_row = '<tr><td>' . $deceased_name . '</td><td>' . $hebrew_deceased_date . '</td><td>' . $english_deceased_date .
                '</td><td>' . $yahrzeit_date_display . '</td><td>' . $relationship_to_mourner . '</td><td>' . $yahrzeit_type_formatted . '</td> </tr>';


        $cur_cid_html = $cur_cid_html . $yahrzeit_html_row;
        $prev_cid = $cur_cid;
      }

      $dao->free();

      if ($prev_cid != "") {
        // Wrap up table for previous contact.
        $cur_cid_html = $cur_cid_html . $html_table_end;
        $values[$prev_cid][$token_strings['all']] = $cur_cid_html;
      }
    }


    // Set default "no yahrzeits found" message for contacts without a value in the 'yahrzeit.all' token.
    foreach ($contactIDs as $cid) {
      if (array_key_exists($cid, $values)) {
        if ($values[$cid][$token_strings['all']] == "") {
          $values[$cid][$token_strings['all']] = $values[$cid][$token_strings['short']] = "No yahrzeits found.";
        }
      }
    }

    // Determine yahrzeit date based on yahrzeit.filter___days_advance_N token,
    // if it's in use.
    $days_advance_token_string = preg_replace('/^yahrzeit\./', '', $token_strings['filter___days_advance_']);
    $yahrzeit_filter_dates = array();
    foreach ($tokens['yahrzeit'] as $token) {
      preg_match("/^{$days_advance_token_string}([0-9]+)/", $token, $matches);
      if (!empty($matches[1])) {
        $yahrzeit_filter_dates[] = $matches[1];
      }
    }

    $yahrzeit_filter_date = date('Ymd', strtotime('+' . min($yahrzeit_filter_dates) . 'days'));

    // Determine whether we're coming from the upcoming Yahrzeits custom search.
    // Default is FALSE.
    $flag_is_custom_search = FALSE;
    // Get ID of UpcomingYahrzeits custom search
    $params = array(
      'name' => 'CRM_Contact_Form_Search_Custom_UpcomingYahrzeits',
      'sequential' => TRUE,
    );
    $result = civicrm_api3('custom_search', 'get', $params);
    if (!empty($result['values']) && !empty($result['values'][0]['value'])) {
      $custom_search_csid = $result['values'][0]['value'];
      // Determine whether we're coming from the upcoming Yahrzeits custom search.
      $url_components = parse_url(CRM_Utils_Request::retrieve('entryURL', 'String'));
      parse_str(html_entity_decode($url_components['query']), $url_query);
      if (
              $url_components['path'] == '/civicrm/contact/search/custom'
              && !empty($url_query['csid'])
              && $url_query['csid'] == $custom_search_csid
      ) {
        $flag_is_custom_search = TRUE;
      }
    }

    // Populate tokens if possible.
    if ($flag_is_custom_search && !empty($_SESSION['yahrzeit_sql'])) {
      // If we're coming from the Yahrzeits custom search, use the SQL defined in
      // that search to get values for all contacts returned in that search.
      $yahrzeit_sql = $_SESSION['yahrzeit_sql'];
      $yahrzeit_sql_params = CRM_Core_DAO::$_nullArray;
    } elseif (!empty($yahrzeit_filter_date)) {
      // If we're not coming from the Yahrzeit custom search, use the
      // 'yahrzeit.filter___days_advance_N' filter to determine a date on which
      // to check for yahrzeits, and work that into an SQL query that can be
      // used to get the correct token values.
      $yahrzeit_sql = "
        SELECT
          contact_b.mourner_contact_id as contact_id,
          contact_b.deceased_contact_id as deceased_contact_id,
          contact_b.mourner_name as sort_name,
          contact_b.deceased_name as deceased_name,
          contact_b.deceased_display_name as deceased_display_name,
          date_format(contact_deceased.deceased_date, '%M %e, %Y' )  as deceased_date,
          contact_b.yahrzeit_hebrew_date_format_hebrew,
          contact_b.yahrzeit_hebrew_date_format_english,
          contact_b.hebrew_deceased_date,
          date_format(contact_b.yahrzeit_date, '%Y-%m-%d' ) as yahrzeit_date_sort,
          date_format(contact_b.yahrzeit_date, '%M %e, %Y' ) as yahrzeit_date_display,
          contact_b.relationship_name_formatted,
          if( contact_b.mourner_observance_preference, date_format(contact_b.yahrzeit_date_morning ,'%M %e, %Y' ), date_format( contact_b.yahrzeit_date_morning, '%M %e, %Y')) as yahrzeit_morning_format_english,
          date_format( contact_b.yahrzeit_erev_shabbat_before, '%M %e, %Y' ) as yah_erev_shabbat_before,
          date_format( contact_b.yahrzeit_erev_shabbat_after, '%M %e, %Y' ) as yah_erev_shabbat_after
        FROM
          pogstone_temp_yahrzeits contact_b
          LEFT JOIN civicrm_contact contact_a ON contact_a.id =  contact_b.mourner_contact_id
          LEFT JOIN civicrm_contact contact_deceased on contact_deceased.id = contact_b.deceased_contact_id
        WHERE
          contact_deceased.is_deleted <> 1
          AND ( contact_a.id is null OR contact_a.is_deleted <> 1 )
          AND ( contact_a.contact_type IN ( 'Household', 'Individual')  AND contact_a.is_deceased <> 1 )
          AND (yahrzeit_type = mourner_observance_preference)
          AND yahrzeit_date = %1
          AND contact_b.mourner_contact_id in (" . implode(',', $contactIDs) . ")
        GROUP BY mourner_contact_id, deceased_contact_id, yahrzeit_date
        ORDER BY yahrzeit_date, deceased_name ASC
      ";
      $yahrzeit_sql_params = array(
        1 => array($yahrzeit_filter_date, 'Date'),
      );
    }
    $dao = & CRM_Core_DAO::executeQuery($yahrzeit_sql, $yahrzeit_sql_params);

    // Figure out how to format date for this locale
    $config = CRM_Core_Config::singleton( );

    $tmp_system_date_format = $config->dateInputFormat;
    if ($tmp_system_date_format == 'dd/mm/yy') {
      $gregorian_date_format = "j F Y";
    } else if ($tmp_system_date_format == 'mm/dd/yy') {
      $gregorian_date_format = "F j, Y";
    } else {
      print "<br>Configuration Issue: Unrecognized System date format: " . $tmp_system_date_format;
    }


    $tmp_deceasedids_for_con = array();
// print "<br<br>About to process yahrzeit records. ";
    while ($dao->fetch()) {

      //  print "<br>Have yahrzeit record!";
      $cid = $dao->contact_id;
      $mourner_name = $dao->sort_name;
      $deceased_name = $dao->deceased_name;
      $deceased_display_name = $dao->deceased_display_name;
      $english_deceased_date = $dao->deceased_date;
      $hebrew_deceased_date = $dao->hebrew_deceased_date;
      $yahrzeit_date = $dao->yahrzeit_date;
      $yahrzeit_date_display = $dao->yahrzeit_date_display;
      $relationship_name_formatted = $dao->relationship_name_formatted;
      $yahrzeit_hebrew_date_format_english = $dao->yahrzeit_hebrew_date_format_english;
      // TODO: Put next field into a token.
      $yahrzeit_hebrew_date_format_hebrew = $dao->yahrzeit_hebrew_date_format_hebrew;
      $yahrzeit_date_raw = $dao->yahrzeit_date_sort;
      $yahrzeit_morning_format_english = $dao->yahrzeit_morning_format_english;
      // $token_strings['english_date_morning']


      $default_seperator = ", ";

      if (array_key_exists($cid, $values)) {
        // print "<br>Fill in token values.";
        $arr_dec_ids = explode(";", $tmp_deceasedids_for_con[$cid]);
        if (in_array($dao->deceased_contact_id, $arr_dec_ids) == false) {



          $tmp_deceasedids_for_con[$cid] = $tmp_deceasedids_for_con[$cid] . ";" . $dao->deceased_contact_id;
          if (strlen($values[$cid][$token_strings['deceased_name']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };

          $values[$cid][$token_strings['deceased_name']] = $values[$cid][$token_strings['deceased_name']] . $seper . $deceased_display_name;

          if (strlen($values[$cid][$token_strings['english_date']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['english_date']] = $values[$cid][$token_strings['english_date']] . $seper . $yahrzeit_date_display;

          if (strlen($values[$cid][$token_strings['hebrew_date']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['hebrew_date']] = $values[$cid][$token_strings['hebrew_date']] . $seper . $yahrzeit_hebrew_date_format_english;

          if (strlen($values[$cid][$token_strings['death_english_date']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['death_english_date']] = $values[$cid][$token_strings['death_english_date']] . $seper . $english_deceased_date;

          if (strlen($values[$cid][$token_strings['death_hebrew_date']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['death_hebrew_date']] = $values[$cid][$token_strings['death_hebrew_date']] . $seper . $hebrew_deceased_date;

          if (strlen($values[$cid][$token_strings['relationship_name']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['relationship_name']] = $values[$cid][$token_strings['relationship_name']] . $seper . $relationship_name_formatted;

          if (strlen($values[$cid][$token_strings['english_date_morning']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['english_date_morning']] = $values[$cid][$token_strings['english_date_morning']] . $seper . $yahrzeit_morning_format_english;

          // take care of tokens for Friday, Saturday before the yahrzeit, and the Friday, Saturday after the yahrzeit.
          $yah_timestamp = strtotime($yahrzeit_date_raw);
          $yah_day_of_week = date('w', $yah_timestamp);


          if ($yah_day_of_week == 5) {
            // The yahrzeit starts at erev Shabbat (ie Friday night), return the yahrzeit date itself.
            // A synagogue in this situation will read the name during services that same shabbat.
            $formatted_friday_before = date($gregorian_date_format, $yah_timestamp);
            $formatted_friday_after = date($gregorian_date_format, $yah_timestamp);

            // Since the yahrzeit itself is a Friday, shabbat morning is the next day.
            $formatted_saturday_before = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " +1 day"));
            $formatted_saturday_after = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " +1 day"));
          } else if ($yah_day_of_week == 6) {
            // The yahrzeit starts on a Saturday night.
            // So the Shabbat morning before the yahrzeit is the same English date as the start of the yahrzeit date.
            $formatted_saturday_before = date($gregorian_date_format, $yah_timestamp);
            $formatted_saturday_after = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " next Saturday"));

            // Do the usual process for getting erev Shabbat before and after.
            $formatted_friday_before = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " previous Friday"));
            $formatted_friday_after = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " next Friday"));
          } else {
            $formatted_friday_before = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " previous Friday"));
            $formatted_friday_after = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " next Friday"));

            $formatted_saturday_before = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " previous Saturday"));
            $formatted_saturday_after = date($gregorian_date_format, strtotime(date("Y-m-d", $yah_timestamp) . " next Saturday"));
          }

          if (strlen($values[$cid][$token_strings['erev_shabbat_before']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['erev_shabbat_before']] = $values[$cid][$token_strings['erev_shabbat_before']] . $seper . $formatted_friday_before;

          if (strlen($values[$cid][$token_strings['shabbat_morning_before']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['shabbat_morning_before']] = $values[$cid][$token_strings['shabbat_morning_before']] . $seper . $formatted_saturday_before;

          if (strlen($values[$cid][$token_strings['erev_shabbat_after']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['erev_shabbat_after']] = $values[$cid][$token_strings['erev_shabbat_after']] . $seper . $formatted_friday_after;

          if (strlen($values[$cid][$token_strings['shabbat_morning_after']]) > 0) {
            $seper = $default_seperator;
          } else {
            $seper = "";
          };
          $values[$cid][$token_strings['shabbat_morning_after']] = $values[$cid][$token_strings['shabbat_morning_after']] . $seper . $formatted_saturday_after;
        }
      }
    }

    $dao->free();
  }

  /**
   * TODO - document
   */
  function util_get_hebrew_month_name(&$julian_date, &$hebrew_date) {
    list($hebrewMonth, $hebrewDay, $hebrewYear) = split('/', $hebrew_date);

    if ($hebrewMonth == '6') {
      /* Its Adar or AdarI */
      /* Check if the 1st of Adar II is a valid day. If it is, then its a leap year. */
      $tmp_adarII_month = '7';
      $tmp_adarII_day = '01';

      $hebrew_leap_year = self::verify_hebrew_date($hebrewYear, $tmp_adarII_month, $tmp_adarII_day);
      if ($hebrew_leap_year == '1') {
        return 'AdarI';
      } else {
        return 'Adar';
      }
    } else {
      /* Its not Adar, so just use the PHP function to get the month name. */
      return jdmonthname($julian_date, 4);
    }
  }

  /**
   * Return true if the Hebrew year is a leap year. Otherwise
   * return false.
   */
  function is_hebrew_year_leap_year($hebrewYear) {

    /* Check if the 1st of Adar II is a valid day. If it is, then its a leap year. */
    $tmp_adarII_month = '7';
    $tmp_adarII_day = '01';

    $hebrew_leap_year = self::verify_hebrew_date($hebrewYear, $tmp_adarII_month, $tmp_adarII_day);
    if ($hebrew_leap_year == '1') {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Get the current date from the server and return the
   * formatted Hebrew Date.
   */
  function util_convert_today2hebrew_date(&$hebrew_format) {
    $today = date_create();

    $gregorianMonth = $today->format('n');
    $gregorianDay = $today->format('j');
    $gregorianYear = $today->format('Y');

    //date_default_timezone_set('America/Chicago');
    $ibeforesunset = '1';
    $today_hebrew_formated = self::util_convert2hebrew_date($gregorianYear, $gregorianMonth, $gregorianDay, $ibeforesunset, $hebrew_format);
    return $today_hebrew_formated;
  }

  /**
   * TODO - document
   */
  function util_get_bar_bat_mizvah_date(&$iyear, &$imonth, &$iday, &$ibeforesunset, &$erev_start_flag, &$bar_bat_mitzvah_flag, &$gregorian_date_format) {
    # date_default_timezone_set('America/Chicago');
    $heb_format_tmp = 'mm/dd/yy';
    $birthdate_hebrew = self::util_convert2hebrew_date($iyear, $imonth, $iday, $ibeforesunset, $heb_format_tmp);

    //  birthdate_hebrew ( will be used for bar bat Mitzvah calculation: $birthdate_hebrew ;

    list($hebrewbirthMonth, $hebrewbirthDay, $hebrewbirthYear) = split('/', $birthdate_hebrew);

    $bar_bat_mitzvah_year = '';

    if ($bar_bat_mitzvah_flag == 'bat') {
      // Technically a girl can be done as early as 12, but most congregations wait until 13.
      $bar_bat_mitzvah_year = $hebrewbirthYear + 13;
    } elseif ($bar_bat_mitzvah_flag == 'bar') {
      $bar_bat_mitzvah_year = $hebrewbirthYear + 13;
    } else {
      return "bar_bat_mitzvah_flag must be either bar or bat.";
    }

    $tmpformat = 'MM dd, yy sunset';
    $bar_bat_gregorian_date = self::util_convert_hebrew2gregorian_date($bar_bat_mitzvah_year, $hebrewbirthMonth, $hebrewbirthDay, $erev_start_flag, $tmpformat);

    if ($bar_bat_gregorian_date == "Date requested does not exist.") {
      $purpose = 'barbat';
      $bar_bat_gregorian_date = self::util_adjust_hebrew_date($bar_bat_mitzvah_year, $hebrewbirthMonth, $hebrewbirthDay, $erev_start_flag, $tmpformat, $purpose);
      $heb_date_is_adjusted = " (adjusted)";
    }

    return $bar_bat_gregorian_date;
  }

  /**
   * Adjusts an invalid Hebrew date to a valid one. For example: Kieslev 30 becomes Kieslev 29.
   * Then converts the adjusted date to a Gregorian date. The Gregorian date is returned.
   */
  function util_adjust_hebrew_date(&$ihyear, &$ihmonth, &$ihday, &$erev_start_flag, &$tmpformat, &$purpose) {
    // Adjusting Hebrew date mm-dd-yyyy:   $ihmonth-$ihday-$ihyear;
    $tmp_hday = $ihday;
    $tmp_hmonth = $ihmonth;

    if ($ihmonth == '6' && $ihday == '30') {
      // Original date was Adar I 30 , ie Rosh Hodesh Adar II during a leap year. This means move date back to Shevat 30, which is also Rosh Hodesh Adar.
      $tmp_hmonth = '5';
    } elseif ($ihmonth == '2' || $ihmonth == '3') {
      if ($ihday == '30') {
        $tmp_hday = '29';
      }
    }

    /* If the month is Adar II , change it to Adar. */
    if ($ihmonth == '7') {
      $tmp_hmonth = '6';
    }

    return self::util_convert_hebrew2gregorian_date($ihyear, $tmp_hmonth, $tmp_hday, $erev_start_flag, $tmpformat);
  }

  /*   * ********************************************************************************
   * Input is Gregorian date of death, plus if the death
   *  occured before sunset or not.
   * it returns the formatted Gregorian date of the yarhzeit.
   * ********************************************************************************* */

  function util_get_next_yahrzeit_date(&$iyear, &$imonth, &$iday, &$gregorian_ibeforesunset, $erev_start_flag, &$gregorian_format) {
    $next_flag = 'next';
    return self::util_get_yahrzeit_date($next_flag, $iyear, $imonth, $iday, $gregorian_ibeforesunset, $erev_start_flag, $gregorian_format);
  }

  function util_get_yahrzeit_date($previous_next_flag, $iyear, $imonth, $iday, $gregorian_ibeforesunset, $erev_start_flag, $gregorian_format) {
    $defaultmsg = "Cannot determine yahrzeit date";

    if ($iyear == '') {
      return $defaultmsg . " because year is blank";
    }

    if ($imonth == '') {
      return $defaultmsg . " because month is blank";
    }

    if ($iday == '') {
      return $defaultmsg . " because day is blank";
    }

    if ($gregorian_ibeforesunset == '') {
      return $defaultmsg . " because before sunset flag is blank";
    }

    // date_default_timezone_set('America/Chicago');

    $heb_date_is_adjusted = "";
    $heb_format_tmp = 'mm/dd/yy';
    $deathdate_hebrew = self::util_convert2hebrew_date($iyear, $imonth, $iday, $gregorian_ibeforesunset, $heb_format_tmp);

    list($hebrewdeathMonth, $hebrewdeathDay, $hebrewdeathYear) = split('/', $deathdate_hebrew);

    $heb_year_format = "yy";
    $current_hebrew_year = self::util_convert_today2hebrew_date($heb_year_format);

    # Get yahrzeit date for the current Hebrew year.
    $tmpformat = 'yyyy-mm-dd';
    $purpose = "yahrzeit";
    $current_year_yahrzetit = self::util_convert_hebrew2gregorian_date($current_hebrew_year, $hebrewdeathMonth, $hebrewdeathDay, $erev_start_flag, $tmpformat);

    if ($current_year_yahrzetit == "Date requested does not exist.") {
      $current_year_yahrzetit = self::util_adjust_hebrew_date($current_hebrew_year, $hebrewdeathMonth, $hebrewdeathDay, $erev_start_flag, $tmpformat, $purpose);
      $heb_date_is_adjusted = " (adjusted)";

      //print "  post-adjusted: ".$current_year_yahrzetit;
      /*  return  "Date requested does not exist this year. Ask the Rabbi.";   */
    } elseif ($current_year_yahrzetit == "Cannot determine Hebrew date") {
      return "Cannot determine Hebrew date for current year yahrzeit";
    }

    $correct_yarzheit = '';
    $yesterday = date(mktime(0, 0, 0, date("m"), date("d") - 1, date("Y")));
    $yesterday_formatted = date('M j Y', $yesterday);

    //$today = date(); 

    if ($previous_next_flag == 'next') {
      if (strtotime($current_year_yahrzetit) >= $yesterday) {
        // Current year yarhzeit: $current_year_yahrzetit  is equal to or later than yesterday: $yesterday.
        $correct_yarzheit = $current_year_yahrzetit;
      } else {
        // Current year yarhzeit has already past the date: $yesterday. Advance hebrew year by 1.
        $next_hebrew_year = $current_hebrew_year + 1;
        $full_format = 'MM dd, yy sunset';
        $heb_date_is_adjusted = "";
        $next_year_yahrzetit = self::util_convert_hebrew2gregorian_date($next_hebrew_year, $hebrewdeathMonth, $hebrewdeathDay, $erev_start_flag, $tmpformat);
        if ($next_year_yahrzetit == "Date requested does not exist.") {


          $next_year_yahrzetit = self::util_adjust_hebrew_date($next_hebrew_year, $hebrewdeathMonth, $hebrewdeathDay, $erev_start_flag, $tmpformat, $purpose);
          $heb_date_is_adjusted = " (adjusted)";
        }


        $correct_yarzheit = $next_year_yahrzetit;
      }
    } else {
      // print "<br>flag means get previous yahr.";
      // get previous yahrzeit.
      if (strtotime($current_year_yahrzetit) < $yesterday) {
        // Current year yarhzeit: $current_year_yahrzetit  is before yesterday: $yesterday.
        $correct_yarzheit = $current_year_yahrzetit;
      } else {
        // Current hebrew year yarhzeit in the future:  Subtract 1 from hebrew year.
        $prev_hebrew_year = $current_hebrew_year - 1;
        $full_format = 'MM dd, yy sunset';
        $heb_date_is_adjusted = "";
        $prev_year_yahrzetit = self::util_convert_hebrew2gregorian_date($prev_hebrew_year, $hebrewdeathMonth, $hebrewdeathDay, $erev_start_flag, $tmpformat);

        // print "<br><br>Just got prev. year yahrzeit: ".$prev_year_yahrzetit;
        if ($prev_year_yahrzetit == "Date requested does not exist.") {


          $prev_year_yahrzetit = self::util_adjust_hebrew_date($prev_hebrew_year, $hebrewdeathMonth, $hebrewdeathDay, $erev_start_flag, $tmpformat, $purpose);
          $heb_date_is_adjusted = " (adjusted)";
        }


        $correct_yarzheit = $prev_year_yahrzetit;
      }
    }

    $tmp_date = date_create($correct_yarzheit);

    if ($tmp_date == "") {
      return "Cannot determine yahrzeit";
    }

    $gregorianMonthName = $tmp_date->format('F');
    $gregorianDay = $tmp_date->format('j');
    $gregorianYear = $tmp_date->format('Y');

    if ($gregorian_format == "MM dd, yyyy") {
      $gregorianMonthName = $tmp_date->format('F');
      $gregorianDay = $tmp_date->format('j');
      $gregorianYear = $tmp_date->format('Y');
      $yahrzeit_date_formatted = "$gregorianMonthName $gregorianDay, $gregorianYear starting at sunset $heb_date_is_adjusted";

      return $yahrzeit_date_formatted;
    } elseif ($gregorian_format == "dd MM yyyy") {
      $gregorianMonthName = $tmp_date->format('F');
      $gregorianDay = $tmp_date->format('j');
      $gregorianYear = $tmp_date->format('Y');
      $yahrzeit_date_formatted = "$gregorianDay $gregorianMonthName $gregorianYear starting at sunset $heb_date_is_adjusted";

      return $yahrzeit_date_formatted;
    } elseif ($gregorian_format == "yyyy-mm-dd") {
      $gregorianMonth = $tmp_date->format('m');
      $gregorianDay = $tmp_date->format('d');
      $gregorianYear = $tmp_date->format('Y');
      $yahrzeit_date_formatted = "$gregorianYear-$gregorianMonth-$gregorianDay";
      return $yahrzeit_date_formatted;
    } else {
      return "unrecognized gregorian format: $gregorian_format";
    }
  }

  /**
   * This function verifies if the Hebrew date exists in reality. For example, 3 Hebrew months 
   * are variable length months. Adar, Heshvan, and Kieslev have either 29 or 30 days depending on
   * the year. The conversion from Julian to Hebrew ALWAYS produces a legit Hebrew date. The conversion from Hebrew to
   * Julian is not always accurate. By going both ways and verifying the results match, we can be certain
   * the Hebrew date is valid. 
   * If valid date, return 1. Else return 0.
   */
  function verify_hebrew_date(&$hebyear, &$hebmonth, &$hebday) {
    if ($hebyear == '' || $hebmonth == '' || $hebday == '') {
      // verify_hebrew_date function error:  year, month and day are all required. 
      return 0;
    }

    $julian_datetmp = cal_to_jd(CAL_JEWISH, $hebmonth, $hebday, $hebyear);
    $hebrewDate_tmp = jdtojewish($julian_datetmp);

    list($hebrewMonth_tmp, $hebrewDay_tmp, $hebrewYear_tmp) = split('/', $hebrewDate_tmp);
    // Hebrew date before: $hebmonth-$hebday-$hebyear / after round trip (mm-dd-yyyy): $hebrewMonth_tmp-$hebrewDay_tmp-$hebrewYear_tmp
    // By: allen@joineryhq.com
    // Date: 2016-08-02
    // Re: https://pogstone.zendesk.com/agent/tickets/9289
    //
    // Per http://php.net/manual/en/function.jdtojewish.php#116453,
    // jdtojewish() returns different values for Adar in non-leap-years,
    // depending on PHP version:
    //   phpversion() < 5.5: Adar returns as month 6
    //   phpversion() >= 5.5: Adar returns as month 7
    // Therefore, adjust 7 to 6 under relevant circumstances, for verifaction purposes.
    static $phpversion;
    if (!isset($phpversion)) {
      $phpversion = phpversion();
    }

    if ($hebmonth == "6" && $hebrewMonth_tmp == "7" && version_compare($phpversion, "5.5", ">=")) {
      $hebrewMonth_tmp = "6";
    }

    if ($hebrewMonth_tmp == $hebmonth && $hebrewDay_tmp == $hebday && $hebrewYear_tmp == $hebyear) {
      return 1;
    } else {
      return 0;
    }
  }

  /**
   * TODO - document
   */
  function util_convert2hebrew_date(&$iyear, &$imonth, &$iday, &$ibeforesunset, &$hebrewformat) {

    $defaultmsg = "Cannot determine Hebrew date";
    if ($iyear == '') {
      return $defaultmsg . " because year is blank";
    }

    if ($imonth == '') {
      return $defaultmsg . " because month is blank";
    }

    if ($iday == '') {
      return $defaultmsg . " because day is blank";
    }

    if ($ibeforesunset == '') {
      return $defaultmsg . " because before sunset flag is blank";
    }

    # date_default_timezone_set('Europe/London');
    $idate_tmp = new DateTime("$iyear-$imonth-$iday");

    $idate_str = $idate_tmp->format('F j, Y');
    // Date provided: $idate_str  

    $sunset_info_formated = '';

    if ($ibeforesunset == "0") {
      $tmpdate_unix = mktime(0, 0, 0, $idate_tmp->format('m'), $idate_tmp->format('d') + 1, $idate_tmp->format('Y'));
      $tmpdate_array = getdate($tmpdate_unix);

      $gregorianMonth = $tmpdate_array['mon'];
      $gregorianDay = $tmpdate_array['mday'];
      $gregorianYear = $tmpdate_array['year'];
      // After sunset, so added 1 day to Gregorian date.

      $sunset_info_formated = '';
    } elseif ($ibeforesunset == "1") {
      $gregorianMonth = $idate_tmp->format('n');
      $gregorianDay = $idate_tmp->format('j');
      $gregorianYear = $idate_tmp->format('Y');

      $sunset_info_formated = ' until sunset';
      // Before sunset, so no change to Gregorian date.
    } else {
      return "Cannot determine Hebrew date because ibeforesunset is not 1 or 0.";
    }

    // Date to convert to Hebrew date( mm-dd-yyyy) :  $gregorianMonth - $gregorianDay - $gregorianYear 
    $jdDate = gregoriantojd($gregorianMonth, $gregorianDay, $gregorianYear);

    if ($hebrewformat == 'mm/dd/yy') {
      $hebrewDate = jdtojewish($jdDate);
      list($hebrewMonth, $hebrewDay, $hebrewYear) = split('/', $hebrewDate);
      $hebrew_date_formated = "$hebrewMonth/$hebrewDay/$hebrewYear";
    } elseif ($hebrewformat == 'dd MM yy sunset') {
      $hebrewDate = jdtojewish($jdDate);
      list($hebrewMonth, $hebrewDay, $hebrewYear) = split('/', $hebrewDate);
      $hebrewMonthName = self::util_get_hebrew_month_name($jdDate, $hebrewDate);
      $hebrew_date_formated = "$hebrewDay  $hebrewMonthName  $hebrewYear $sunset_info_formated";
    } elseif ($hebrewformat == 'dd MM yy' || $hebrewformat == 'dd_MM_yy') {
      $hebrewDate = jdtojewish($jdDate);
      list($hebrewMonth, $hebrewDay, $hebrewYear) = split('/', $hebrewDate);
      $hebrewMonthName = self::util_get_hebrew_month_name($jdDate, $hebrewDate);
      $hebrew_date_formated = "$hebrewDay $hebrewMonthName $hebrewYear";
    } elseif ($hebrewformat == 'dd MM' || $hebrewformat == 'dd_MM') {
      $hebrewDate = jdtojewish($jdDate);
      list($hebrewMonth, $hebrewDay, $hebrewYear) = split('/', $hebrewDate);
      $hebrewMonthName = self::util_get_hebrew_month_name($jdDate, $hebrewDate);
      $hebrew_date_formated = "$hebrewDay $hebrewMonthName";
    } elseif ($hebrewformat == 'yy') {
      $hebrewDate = jdtojewish($jdDate);
      list($hebrewMonth, $hebrewDay, $hebrewYear) = split('/', $hebrewDate);
      $hebrew_date_formated = "$hebrewYear";
    } elseif ($hebrewformat == 'hebrew') {
      $hebrew_date_formated = mb_convert_encoding(jdtojewish($jdDate, true), "UTF-8", "ISO-8859-8");
    } else {
      $hebrew_date_formated = "Unrecognized Hebrew date format: $hebrewformat";
    }

    // Hebrew Date formatted: $hebrew_date_formated 
    return $hebrew_date_formated;
  }

  /**
   * Input parm is the Hebrew date and desired format. The Gregorian
   * date is returned as a formated string.  
   */
  function util_convert_hebrew2gregorian_date(&$iyear, &$imonth, &$iday, &$erev_start_flag, &$date_format) {

    if ($imonth == '') {

      return "Month is required";
    } else if ($iday == '') {
      return "Day is required";
    } else if ($iyear == '') {

      return "year is required";
    } else {

      $valid_hebrew_date = self::verify_hebrew_date($iyear, $imonth, $iday);

      if ($valid_hebrew_date == 0) {
        return "Date requested does not exist.";
      }

      $julian_date = cal_to_jd(CAL_JEWISH, $imonth, $iday, $iyear);
      $gregorian_date = cal_from_jd($julian_date, CAL_GREGORIAN);


      $oDay = $gregorian_date['day'];
      $oYear = $gregorian_date['year'];
      $oMonthName = $gregorian_date['monthname'];
      $oMonth = $gregorian_date['month'];

      if ($erev_start_flag == '0') {
        $sunset_str = "until sunset";
        // wanted ending date, no change needed to Gregorian date.
      } else if ($erev_start_flag == '1') {

        $sunset_str = "starting at sunset";
        $tmpdate_unix = mktime(0, 0, 0, $oMonth, $oDay - 1, $oYear);
        $tmpdate_array = getdate($tmpdate_unix);


        $oMonthName = $tmpdate_array['month'];
        $oMonth = $tmpdate_array['mon'];
        $oDay = $tmpdate_array['mday'];
        $oYear = $tmpdate_array['year'];
        // wanted Erev ( ie starting date), so subtracted 1 day from Gregorian date.
      } else {
        return "Unknown erev_start_flag, must be either '1' or '0' ";
      }

      $formatted_date_str = '';
      if ($date_format == 'yyyy-mm-dd') {
        $dash = "-";
        $formatted_date_str = $oYear . $dash . $oMonth . $dash . $oDay;
        // Is Hebrew date valid?  $valid_str
      } else if ($date_format == 'dd-mm-yyyy') {
        $slash = '-';
        $formatted_date_str = $oDay . $slash . $oMonth . $slash . $oYear;
      } else if ($date_format == 'MM dd, yy sunset') {
        // numeric month: $oMonth

        $formatted_date_str = "$oMonthName $oDay, $oYear $sunset_str";
      } else {
        $formatted_date_str = "Unknown date_format.";
      }
      // function util_convert_hebrew2gregorian_date about to return: $formatted_date_str
      // print "<br>".$formatted_date_str."<br>";
      return $formatted_date_str;
    }
  }

  public function get_sql_table_name() {
    $yahrzeit_table_name = 'pogstone_temp_yahrzeits';
    $tmp_table_name = $yahrzeit_table_name;

    return $tmp_table_name;

    /*

      // check if table with this key already exists.
      //$table_missing = true;

      $cur_schema_name = self::getSQLschema();

      $table_sql = "SELECT table_name FROM information_schema.tables
      WHERE
      table_schema = '$cur_schema_name'
      AND table_name = '$tmp_table_name'"  ;


      //  print "<Br>sql: ".$table_sql;
      $table_dao =& CRM_Core_DAO::executeQuery( $table_sql ,   CRM_Core_DAO::$_nullArray ) ;

      //   print "<br>sql: ".$yahrzeit_sql;
      if( $table_dao->fetch( ) ) {
      // print "<br>Table already exists.";
      // $table_missing = false;
      }else{
      // print "<br>Table does NOT exist.";
      self::buildTempTable($tmp_table_name);
      }


      $table_dao->free();

      $temp_table_data_needs_refresh  = true;

      // print "<br>Check if data is stale";
      // TODO: If data is stale, rebuild it. Also remove old records.
      if(self::ALWAYS_CLEAR_TEMP_TABLE){
      // this is typically only set in development environments.
      $temp_table_data_needs_refresh  = true;

      }else{
      //      print "<br>About to call freshness function";
      $temp_table_data_needs_refresh  =  self::CheckFreshnessTempTable($yahrzeit_table_name );
      //	print "<br> done with freshness function";

      }
      if( $temp_table_data_needs_refresh ){
      //print "<br>Data needs refresh, refill temp table: ".$tmp_table_name;
      self::fillTempTable($tmp_table_name, false);
      }else{
      //print "<br>Data is okay, use existing records.";
      }

      return $tmp_table_name;
     */
  }

  public static function getSQLschema() {
    $tmp_schema_name = '';
    $sql = "SELECT SCHEMA() as tmp_sname from civicrm_contact limit 0, 1";
    $dao = & CRM_Core_DAO::executeQuery($sql, CRM_Core_DAO::$_nullArray);

    if ($dao->fetch()) {
      $tmp_schema_name = $dao->tmp_sname;
    } else {
      print "<br><br>Pogstone message: Cound NOT find schema using query: " . $sql;
    }

    $dao->free();
    // print "<br>About to return schema name: ".$tmp_schema_name;
    return $tmp_schema_name;
  }

  private static function XXXCheckFreshnessTempTable($yahrzeit_table_name) {

    /*
      // Check if its been more than x minutes since last data load.
      $tmp_minutes = self::YAHRZEIT_CACHE_TIMEOUT;

      $sql_str = "SELECT min(TIMEDIFF(now(),created_date)) as time_diff from $yahrzeit_table_name
      having time_diff > '00:$tmp_minutes:00'
      order by time_diff ";

      // print "<br>sql: ".$sql_str;
      $record_found = false;
      $return_needs_refresh = false;
      $dao =& CRM_Core_DAO::executeQuery(  $sql_str ,   CRM_Core_DAO::$_nullArray ) ;

      // print "<br>sql: ".$yahrzeit_sql;
      if( $dao->fetch( ) ) {
      $tmp_min_time = $dao->time_diff;
      $record_found = true;
      // print "<br>Found time diff: ".$tmp_min_time;

      }

      $dao->free( );

      // check for empty table
      $sql_count = "Select count(*) as count from $yahrzeit_table_name";
      $dao_count =& CRM_Core_DAO::executeQuery(  $sql_count ,   CRM_Core_DAO::$_nullArray ) ;


      if( $dao_count->fetch( ) ) {
      $tmp_count = $dao_count->count;
      //print "<Br>Num records in temp table: ".$tmp_count;
      if( $tmp_count == 0){
      self::fillTempTable($yahrzeit_table_name, false);
      }

      }
      $dao_count->free();

      if($record_found){
      // print "<br>Time diff more than limit. Need to remove old records.";
      $return_needs_refresh = true;
      self::removeStaleRecords($yahrzeit_table_name, $tmp_minutes);

      }

      return $return_needs_refresh;

     */
  }

  private static function XXXremoveStaleRecords($yahrzeit_table_name, $tmp_limit) {
    //  $sql_str = "DELETE from $yahrzeit_table_name where TIMEDIFF(now(),created_date) > '00:$tmp_limit:00'";
    //  $dao =& CRM_Core_DAO::executeQuery(  $sql_str ,   CRM_Core_DAO::$_nullArray ) ;
    // self::fillTempTable($yahrzeit_table_name, false);
  }

  function getYahrzeitDateEnglishObservance(&$deceased_year, &$deceased_month, &$deceased_day, &$previous_next_flag) {
    $tmp_return = '';
    $cur_year = date('Y');

    if (strlen($deceased_year) > 0 && strlen($deceased_month) > 0 && strlen($deceased_day) > 0) {


      $tmp_yahrzeit_date_observe_english = new DateTime($cur_year . '-' . $deceased_month . '-' . $deceased_day);
      // Since this function is expected to return the evening of the yahrzeit, need to subtract 1 day.
      $tmp_yahrzeit_date_observe_english = $tmp_yahrzeit_date_observe_english->sub(new DateInterval('P1D'));

      if ($previous_next_flag == 'next') {
        if ($tmp_yahrzeit_date_observe_english < new DateTime()) {
          // add a year.
          $tmp_yahrzeit_date_observe_english->add(new DateInterval('P1Y'));
        }
      } else if ($previous_next_flag == 'prev') {
        // print "<br>Need prev. English yahrzeit date. ";
        if ($tmp_yahrzeit_date_observe_english >= new DateTime()) {
          // subtract a year.
          $tmp_yahrzeit_date_observe_english->sub(new DateInterval('P1Y'));
        }
      } else {
        $tmp_return = "Unknown previous/next flag";
      }


      $tmp_return = $tmp_yahrzeit_date_observe_english->format('Y-m-d');
    } else {
      $tmp_return = "Unknown date";
    }

    // print "<h2>English observed yahrzeit: ".$tmp_yahrzeit_date_observe_english_sql."</h2>";


    return $tmp_return;
  }

  function getYahrzeitDateEnglishObservanceFormated(&$deceased_year, &$deceased_month, &$deceased_day, &$previous_next_flag) {
    $tmp_return = '';
    $cur_year = date('Y');

    if (strlen($deceased_month) == 0) {
      return "Cannot determine yahrzeit date";
    }

    if (strlen($deceased_day) == 0) {
      return "Cannot determine yahrzeit date";
    }

    $tmp_yahrzeit_date_observe_english = new DateTime($cur_year . '-' . $deceased_month . '-' . $deceased_day);

    if ($previous_next_flag == 'next') {
      if ($tmp_yahrzeit_date_observe_english < new DateTime()) {
        // add a year.
        $tmp_yahrzeit_date_observe_english->add(new DateInterval('P1Y'));
      }
    } else if ($previous_next_flag == 'prev') {
      if ($tmp_yahrzeit_date_observe_english >= new DateTime()) {
        // subtract a year.
        $tmp_yahrzeit_date_observe_english->sub(new DateInterval('P1Y'));
      }
    } else {
      print "<br><br>Inside function: getYahrzeitDateEnglishObservanceFormated, unknown previous_next_flag: " . $previous_next_flag;
    }
    $tmp_return = $tmp_yahrzeit_date_observe_english->format('F d, Y');

    return $tmp_return;
  }

}
