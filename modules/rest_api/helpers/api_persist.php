<?php

/**
 * @file
 * Helper class for persisting records to the database.
 *
 * Indicia, the OPAL Online Recording Toolkit.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see http://www.gnu.org/licenses/gpl.html.
 *
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link http://code.google.com/p/indicia/
 */

/**
 * Helper class for data persistence operations for the API.
 *
 * User by the REST API as well as REST API Sync modules for data persitance
 * (create, update & deletes) .
 *
 * @package Services
 * @subpackage REST API
 *
 * @todo Test classes
 * @todo Exceptions (and endpoint) need to return an error code.
 */
class api_persist {

  /**
   * Persists a taxon-observation resource.
   *
   * @param object $db
   *   Database connection object.
   * @param array $observation
   *   Observation data.
   * @param int $website_id
   *   Website ID being saved into.
   * @param int $survey_id
   *   Survey dataset ID being saved into.
   * @param int $taxon_list_id
   *   Taxon list used for species name lookups.
   *
   * @return bool
   *   True if a new record was creates, false if an existing one was updated.
   *
   * @throws \exception
   */
  public static function taxonObservation($db, array $observation, $website_id, $survey_id, $taxon_list_id) {
    // @todo Persist custom attribute values
    if (!empty($observation['taxonVersionKey'])) {
      $lookup = ['search_code' => $observation['taxonVersionKey']];
    }
    elseif (!empty($observation['taxonName'])) {
      $lookup = ['original' => $observation['taxonName']];
    }
    $ttl_id = self::findTaxon($db, $taxon_list_id, $lookup);
    if (!$ttl_id) {
      throw new exception("Could not find taxon for $observation[taxonVersionKey]");
    }
    $values = self::getTaxonObservationValues($db, $website_id, $observation, $ttl_id);

    self::checkMandatoryFields($observation, 'taxon-observation');
    $existing = self::findExistingObservation($db, $observation['id'], $survey_id);
    if (count($existing)) {
      $values['occurrence:id'] = $existing[0]['id'];
      $values['sample:id'] = $existing[0]['sample_id'];
    }
    else {
      $values['sample:survey_id'] = $survey_id;
    }

    // Set the spatial reference depending on the projection information
    // supplied.
    self::setSrefData($values, $observation, 'sample:entered_sref');
    // @todo Should the precision field be stored in a custom attribute?
    // Site handling. If a known site with a SiteKey, we can create a record in
    // locations, otherwise use the free text location_name field.
    if (!empty($observation['SiteKey'])) {
      $values['sample:location_id'] = self::getLocationId($db, $website_id, $observation);
    }
    elseif (!empty($observation['siteName'])) {
      $values['sample:location_name'] = $observation['siteName'];
    }
    $obs = ORM::factory('occurrence');
    $obs->set_submission_data($values);
    $obs->submit();
    if (count($obs->getAllErrors()) !== 0) {
      throw new exception("Error occurred submitting an occurrence\n" . kohana::debug($obs->getAllErrors()));
    }
    return count($existing) === 0;
  }

  /**
   * Persists an annotation resource.
   *
   * @param object $db
   *   Database connection object.
   * @param array $annotation
   *   Annotation data.
   * @param int $survey_id
   *   Survey dataset ID being saved into.
   *
   * @return bool
   *   True if new annotation inserted, false if updated.
   */
  public static function annotation($db, $annotation, $survey_id) {
    self::mapRecordStatus($annotation);
    // Set up a values array for the annotation post.
    $values = self::getAnnotationValues($db, $annotation);
    // Link to the existing observation.
    $existingObs = self::findExistingObservation($db, $annotation['taxonObservation']['id'], $survey_id);
    if (!count($existingObs)) {
      // @todo Proper error handling as annotation can't be imported. Perhaps should obtain
      // and import the observation via the API?
      throw new exception("Attempt to import annotation $annotation[id] but taxon observation not found.");
    }
    $values['occurrence_comment:occurrence_id'] = $existingObs[0]['id'];
    // Link to existing annotation if appropriate.
    $existing = self::findExistingAnnotation($db, $annotation['id'], $existingObs[0]['id']);
    if ($existing) {
      $values['occurrence_comment:id'] = $existing[0]['id'];
    }
    $annotationObj = ORM::factory('occurrence_comment');
    $annotationObj->set_submission_data($values);
    $annotationObj->submit();
    self::updateObservationWithAnnotationDetails($db, $existingObs[0]['id'], $annotation);
    if (count($annotationObj->getAllErrors()) !== 0) {
      throw new exception("Error occurred submitting an annotation\n" . kohana::debug($annotationObj->getAllErrors()));
    }
    return count($existing) === 0;
  }

  /**
   * Builds the values array required to post a taxon-observation resource to the local
   * database.
   *
   * @param object $db
   *   Database connection object.
   * @param int $website_id
   *   Website ID.
   * @param array $observation
   *   Taxon-observation resource data.
   * @param int $ttl_id
   *   ID of the taxa taxon list the observation points to.
   *
   * @return array
   *   Values array to use for submission building.
   *
   * @todo Reuse the last sample if it matches
   */
  private static function getTaxonObservationValues($db, $website_id, array $observation, $ttl_id) {
    $values = array(
      'website_id' => $website_id,
      'sample:date_start'     => $observation['startDate'],
      'sample:date_end'       => $observation['endDate'],
      'sample:date_type'      => $observation['dateType'],
      'sample:recorder_names' => isset($observation['recorder']) ? $observation['recorder'] : 'Unknown',
      'occurrence:taxa_taxon_list_id' => $ttl_id,
      'occurrence:external_key' => $observation['id'],
      'occurrence:zero_abundance' => isset($observation['zeroAbundance']) ? strtolower($observation['zeroAbundance']) : 'f',
      'occurrence:sensitivity_precision' => isset($observation['sensitive'])
        && strtolower($observation['sensitive']) === 't' ? 10000 : NULL,
    );
    if (isset($observation['occAttrs'])) {
      foreach ($observation['occAttrs'] as $id => $value) {
        // Lookup the term if we can.
        $qry = <<<SQL
SELECT t.id
FROM occurrence_attributes a
JOIN cache_termlists_terms t ON t.termlist_id=a.termlist_id AND t.term='$value'
WHERE a.id=$id AND a.data_type='L'
SQL;
        $term = $db->query($qry)->result_array(FALSE);
        // Use the found term ID if we found one. Otherwise submit the iNat
        // value (which will presumably fail validation if this is a lookup).
        $values["occAttr:$id"] = count($term) > 0 ? $term[0]['id'] : $value;
      }
    }
    echo '<pre>';
    var_export($observation);
    echo '</pre><br/>';
    echo '<pre>';
    var_export($values);
    echo '</pre><br/>';
    return $values;
  }

  /**
   * Builds the values array required to post an annotation resource to the db.
   *
   * @param object $db
   *   Database connection object.
   * @param array $annotation
   *   Annotation resource data.
   *
   * @return array
   *   Values array to use for submission building.
   */
  private static function getAnnotationValues($db, array $annotation) {
    return array(
      'occurrence_comment:comment' => $annotation['comment'],
      'occurrence_comment:email_address' => self::valueOrNull($annotation, 'emailAddress'),
      'occurrence_comment:record_status' => self::valueOrNull($annotation, 'record_status'),
      'occurrence_comment:record_substatus' => self::valueOrNull($annotation, 'record_substatus'),
      'occurrence_comment:query' => $annotation['question'],
      'occurrence_comment:person_name' => $annotation['authorName'],
      'occurrence_comment:external_key' => $annotation['id'],
    );
  }

  /**
   * Looks up a taxon's ID from the database.
   *
   * @param object $db
   *   Database instance.
   * @param int $taxon_list_id
   *   Taxon list to lookup against.
   * @param array $lookup
   *   Array of field/value pairs look up (e.g
   *   ['external_key' => '<Taxon version key>'])
   *
   * @return int
   *   Taxa_taxon_list_id of the found record.
   *
   * @throws \exception
   */
  private static function findTaxon($db, $taxon_list_id, array $lookup) {
    $filter = empty($lookup['original'])
      ? '' : "AND searchterm like '" . pg_escape_string($lookup['original']) . "%'\n";
    foreach ($lookup as $key => $value) {
      $filter .= "AND $key='$value'\n";
    }
    $qry = <<<SQL
SELECT taxon_meaning_id, taxa_taxon_list_id
FROM cache_taxon_searchterms
WHERE taxon_list_id=$taxon_list_id
AND simplified='f'
$filter
ORDER BY preferred DESC
SQL;
    $taxa = $db->query($qry)->result_array(FALSE);
    // Need to know if the search found a single unique taxon concept so count
    // the taxon meanings.
    $uniqueConcepts = [];
    foreach ($taxa as $taxon) {
      $uniqueConcepts[$taxon['taxon_meaning_id']] = $taxon['taxon_meaning_id'];
    }
    if (count($uniqueConcepts) === 1) {
      // If we found 1 concept, then the first match will be fine.
      return $taxa[0]['taxa_taxon_list_id'];
    }
    else {
      // If ambiguous about the concept then the search has failed.
      kohana::log('debug', $db->last_query());
      throw new exception('Could not find a unique preferred taxon for lookup ' . json_encode($lookup));
    }
  }

  /**
   * Checks that all the mandatory fields for a given resource type are populated. Returns
   * an array of missing field names, empty if the record is complete.
   *
   * @param $array
   * @param string $resourceName
   *   Name of the resource being checked.
   *
   * @throws \exception
   */
  private static function checkMandatoryFields($array, $resourceName) {
    $required = array();
    // deletions have no other mandatory fields except the id to delete
    if (!empty($resource['delete']) && $resource['delete'] === 'T')
      $array[] = 'id';
    else {
      switch ($resourceName) {
        case 'taxon-observation':
          $required = array(
            'id',
            'href', /*'taxonName', */
            'startDate',
            'endDate',
            'dateType',
            'projection',
            'precision',
            'recorder',
          );
          // Conditionally required fields.
          if (empty($array['gridReference'])) {
            $required[] = 'east';
            $required[] = 'north';
            if (empty($array['east']) || empty($array['north'])) {
              $required[] = 'gridReference';
            }
          }
          $required[] = empty($array['taxonVersionKey']) ? 'taxonName' : 'taxonVersionKey';
          break;

        case 'annotation':
          // @todo Mandatory fields for an annotation.
          break;
      }
    }
    $missing = array_diff($required, array_keys($array));
    if (!empty($missing)) {
      throw new exception("$resourceName has missing mandatory field values: " . implode(', ', $missing));
    }
  }

  /**
   * Finds an existing observation.
   *
   * Retrieve existing observation details from the database for an ID supplied
   * by a call to the REST API.
   *
   * @param object $db
   *   Database instance.
   * @param string $id
   *   The taxon-observation's ID as returned by a call to the REST api.
   * @param int $survey_id
   *   The database survey ID value to lookup within.
   *
   * @return array
   *   Array containing occurrence and sample ID for any existing matching
   *   records.
   */
  private static function findExistingObservation($db, $id, $survey_id) {
    $thisSystemUserId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($thisSystemUserId)) === $thisSystemUserId;
    // Look for an existing record to overwrite.
    $filter = array(
      'o.deleted' => 'f',
      's.deleted' => 'f',
    );
    // @todo Do we want to overwrite existing records which originated here?
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere) {
      $filter['o.id'] = substr($id, strlen($thisSystemUserId));
    }
    else {
      $filter['o.external_key'] = (string) $id;
      $filter['s.survey_id'] = $survey_id;
    }
    $existing = $db->select('o.id, o.sample_id')
      ->from('occurrences o')
      ->join('samples as s', 'o.sample_id', 's.id')
      ->where($filter)
      ->get()->result_array(FALSE);
    return $existing;
  }

  /**
   * Retrieve existing comment details from the database for an annotation ID supplied by
   * a call to the REST API.
   *
   * @param object $db
   *   Database instance.
   * @param string $id
   *   The taxon-observation's ID as returned by a call to the REST api.
   * @param integer $occ_id
   *   The database observation ID value to lookup within.
   * @return array Array containing occurrence comment ID for any existing matching records.
   */
  private static function findExistingAnnotation($db, $id, $occ_id) {
    // @todo Add external key to comments table? OR do we use the timestamp?
    $userId = Kohana::config('rest_api_sync.user_id');
    $recordOriginHere = substr($id, 0, strlen($userId)) === $userId;
    // Look for an existing record to overwrite.
    $filter = array(
      'oc.deleted' => 'f',
      'occurrence_id' => $occ_id
    );
    // @todo What happens if origin here but record missing?
    if ($recordOriginHere) {
      $filter['oc.id'] = substr($id, strlen($userId) - 1);
    }
    else {
      $filter['oc.external_key'] = $id;
    }
    $existing = $db->select('oc.id')
      ->from('occurrence_comments oc')
      ->join('cache_occurrences as o', 'o.id', 'oc.occurrence_id')
      ->where($filter)->get()->result_array(FALSE);
    return $existing;
  }

  /**
   * Uses the data in an observation to set the spatial reference information in a values array
   * before it can be submitted via ORM to the database.
   *
   * @param array $values
   *   The values array to add the spatial reference information to.
   * @param array $observation
   *   The observation data array.
   * @param string $fieldname
   *   The name of the spatial reference field to be set in the values array,
   *   e.g. sample:entered_sref.
   */
  private static function setSrefData(&$values, $observation, $fieldname) {
    if ($observation['projection'] === 'OSGB' || $observation['projection'] === 'OSI') {
      $values[$fieldname] = strtoupper(str_replace(' ', '', $observation['gridReference']));
      $values[$fieldname . '_system'] = $observation['projection'] === 'OSGB' ? 'OSGB' : 'OSIE';
    }
    elseif ($observation['projection'] === 'WGS84') {
      $values[$fieldname] = self::formatLatLong($observation['north'], $observation['east']);
      $values[$fieldname . '_system'] = 4326;
    }
    elseif ($observation['projection'] === 'OSGB36') {
      $values[$fieldname] = self::formatEastNorth($observation['east'], $observation['north']);
      $values[$fieldname . '_system'] = 27700;
    }
  }

  /**
   * Returns a formatted decimal latitude and longitude string.
   *
   * @param float $lat
   *   Latitude.
   * @param float $long
   *   Longitude.
   *
   * @return string
   *   Formatted lat long.
   */
  private static function formatLatLong($lat, $long) {
    $ns = $lat >= 0 ? 'N' : 'S';
    $ew = $long >= 0 ? 'E' : 'W';
    $lat = abs($lat);
    $long = abs($long);
    return "$lat$ns $long$ew";
  }

  /**
   * Returns a formatted decimal east and north string.
   *
   * @param $east
   *   Easting.
   * @param $north
   *   Northing.
   * @return string
   *   Formatted easting/northing.
   */
  private static function formatEastNorth($east, $north) {
    return "$east, $north";
  }

  /**
   * Retrieves the location_id for the locations records associated with an incoming observation.
   * The observation must have a SiteKey specified which will be used to lookup a location linked
   * to the server's website ID. If it does not exist, then it will be created using the observation's
   * spatial reference as a centroid.
   *
   * @param object $db
   *   Database instance.
   * @param int $website_id
   *   ID of the website registration the location should be looked up from.
   * @param array $observation
   *   The observation data array.
   *
   * @return int
   *   The ID of the location record in the database.
   */
  private static function getLocationId($db, $website_id, $observation) {
    $existing = $db->select('l.id')
      ->from('locations as l')
      ->join('locations_websites as lw', 'lw.location_id', 'l.id')
      ->where(array(
        'l.deleted' => 'f',
        'lw.deleted' => 'f',
        'lw.website_id' => $website_id,
        'l.code' => $observation['SiteKey'],
      ))->get()->result_array(FALSE);
    if (count($existing)) {
      return $existing[0]['id'];
    }
    else {
      return self::createLocation($website_id, $observation);
    }
  }

  /**
   * Creates a location in the database from the information supplied in an observation. The
   * observation should have a SiteKey specified so that future observations for the same SiteKey
   * can be linked to the same location.
   *
   * @param int $website_id
   *   ID of the database registration to add the location to.
   * @param array $observation
   *   The observation data array.
   *
   * @return int
   *   The ID of the location record created in the database.
   *
   * @todo Join the location to the server's associated website
   */
  private static function createLocation($website_id, array $observation) {
    $location = ORM::factory('location');
    $values = array(
      'location:code' => $observation['siteKey'],
      'location:name' => $observation['siteName']
    );
    self::setSrefData($values, $observation, 'location:centroid_sref');
    $location->set_submission_data($values);
    $location->submit();
    // @todo Error handling on submission.
    // @todo Link the location to the website we are importing into?
    return $location->id;
  }

  /**
   * Converts the record status codes in an annotation into Indicia codes.
   *
   * @param array $annotation
   *   Annotation data.
   */
  private static function mapRecordStatus(array &$annotation) {
    if (empty($annotation['statusCode1'])) {
      $annotation['record_status'] = NULL;
    }
    else {
      switch ($annotation['statusCode1']) {
        case 'A':
          // Accepted = verified.
          $annotation['record_status'] = 'V';
          break;

        case 'N':
          // Not accepted = rejected.
          $annotation['record_status'] = 'R';
          break;

        default:
          $annotation['record_status'] = 'C';
      }
    }
    if (empty($annotation['statusCode2'])) {
      $annotation['record_substatus'] = NULL;
    }
    else {
      $annotation['record_substatus'] = $annotation['statusCode2'];
    }
  }

  /**
   * If an annotation provides a newer record status or identification than that already
   * associated with an observation, updates the observation.
   *
   * @param int $occurrence_id
   *   ID of the associated occurrence record in the database.
   * @param array $annotation
   *   Annotation object loaded from the REST API.
   *
   * @throws exception
   */
  private static function updateObservationWithAnnotationDetails($db, $occurrence_id, array $annotation) {
    // Find the original record to compare against.
    $oldRecords = $db
      ->select('record_status, record_substatus, taxa_taxon_list_id')
      ->from('cache_occurrences')
      ->where('id', $occurrence_id)
      ->get()->result_array(FALSE);
    if (!count($oldRecords)) {
      throw new exception('Could not find cache_occurrences record associated with a comment.');
    }

    // Find the taxon information supplied with the comment's TVK.
    $newTaxa = $db
      ->select('id, taxonomic_sort_order, taxon, authority, preferred_taxon, default_common_name, search_name, ' .
        'external_key, taxon_meaning_id, taxon_group_id, taxon_group')
      ->from('cache_taxa_taxon_lists')
      ->where([
        'preferred' => 't',
        'external_key' => $annotation['taxonVersionKey'],
        'taxon_list_id' => kohana::config('rest_api_sync.taxon_list_id'),
      ])
      ->limit(1)
      ->get()->result_array(FALSE);
    if (!count($newTaxa)) {
      throw new exception('Could not find cache_taxa_taxon_lists record associated with an update from a comment.');
    }

    $oldRecord = $oldRecords[0];
    $newTaxon = $newTaxa[0];

    $new_status = $annotation['record_status'] === $oldRecord['record_status']
      ? FALSE : $annotation['record_status'];
    $new_substatus = $annotation['record_substatus'] === $oldRecord['record_substatus']
      ? FALSE : $annotation['record_substatus'];
    $new_ttlid = $newTaxon['id'] === $oldRecord['taxa_taxon_list_id']
      ? FALSE : $newTaxon['id'];

    // Does the comment imply an allowable change to the occurrence's attributes?
    if ($new_status || $new_substatus || $new_ttlid) {
      $oupdate = array('updated_on' => date("Ymd H:i:s"));
      $coupdate = array('cache_updated_on' => date("Ymd H:i:s"));
      if ($new_status || $new_substatus) {
        $oupdate['verified_on'] = date("Ymd H:i:s");
        // @todo Verified_by_id needs to be mapped to a proper user account.
        $oupdate['verified_by_id'] = 1;
        $coupdate['verified_on'] = date("Ymd H:i:s");
        $coupdate['verifier'] = $annotation['authorName'];
      }
      if ($new_status) {
        $oupdate['record_status'] = $new_status;
        $coupdate['record_status'] = $new_status;
      }
      if ($new_substatus) {
        $oupdate['record_substatus'] = $new_substatus;
        $coupdate['record_substatus'] = $new_substatus;
      }
      if ($new_ttlid) {
        $oupdate['taxa_taxon_list_id'] = $new_ttlid;
        $coupdate['taxa_taxon_list_id'] = $new_ttlid;
        $coupdate['taxonomic_sort_order'] = $newTaxon['taxonomic_sort_order'];
        $coupdate['taxon'] = $newTaxon['taxon'];
        $coupdate['preferred_taxon'] = $newTaxon['preferred_taxon'];
        $coupdate['authority'] = $newTaxon['authority'];
        $coupdate['default_common_name'] = $newTaxon['default_common_name'];
        $coupdate['search_name'] = $newTaxon['search_name'];
        $coupdate['taxa_taxon_list_external_key'] = $newTaxon['external_key'];
        $coupdate['taxon_meaning_id'] = $newTaxon['taxon_meaning_id'];
        $coupdate['taxon_group_id'] = $newTaxon['taxon_group_id'];
        $coupdate['taxon_group'] = $newTaxon['taxon_group'];
      }
      $db->update('occurrences',
        $oupdate,
        array('id' => $occurrence_id)
      );
      $db->update('cache_occurrences',
        $coupdate,
        array('id' => $occurrence_id)
      );
      // @todo create a determination if this is not automatic
    }
  }

  /**
   * Simple utility function to return a value from an array, or null if not present.
   *
   * @param $array
   * @param $key
   *
   * @return mixed
   */
  private static function valueOrNull($array, $key) {
    return isset($array[$key]) ? $array[$key] : NULL;
  }
}