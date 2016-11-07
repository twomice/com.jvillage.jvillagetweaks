<?php

class CRM_Jmanage_Utils_Timezone {
  /**
   * Converts a Drupal6 timezone offset to a valid timezone name (redmine:1199 and redmine:1172).
   *
   * This is a quick dirty hack and not sure if it's an upstream bug, since we only
   * started encountering it after 4.7.12, but might have become visible for other reasons.
   *
   * FIXME: this function has DST problems. The hardcoded tz work around this.
   */
  static function drupal6_offset_to_tz($offset) {
    // Hardcode a few obvious ones.
    $hardcoded = [
      -18000 => 'America/Chicago',
      -18000 => 'America/Chicago',
      -14400 => 'America/New_York',
      -25200 => 'America/Vancouver',
      39600 => 'Australia/Sydney',
    ];

    if (isset($hardcoded[$offset])) {
      return $hardcoded[$offset];
    }

    // Loop through all timezones and find the closest one.
    // This can return the wrong name, but technically still the same
    // since all we care about is the time.
    // Source: http://drupal.stackexchange.com/questions/37006/convert-d6-user-timezone-offset-to-d7-timezone-string
    $map = [];

    foreach (timezone_identifiers_list() as $zone) {
      if ($tz = timezone_open($zone)) {
        if ($datetime = date_create("now", $tz)) {
          if (($tmp_offset = $tz->getOffset($datetime)) !== FALSE && !isset($map[$tmp_offset])) {
            $map[$tmp_offset][] = $zone;
          }
        }
      }
    }

    if (isset($map[$offset][0])) {
      return $map[$offset][0];
    }

    return NULL;
  }

}
