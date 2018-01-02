<?php

/**
 * @file
 * Helper class for PostgreSQL specific query generation.
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
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Helper class to provide postgreSQL specific SQL functions, so that they can all be
 * kept in one place.
 */
class postgreSQL {

  public static function transformWkt($wkt, $fromSrid, $toSrid, $db = NULL) {
    if ($fromSrid != $toSrid) {
      if (!$db) {
        $db = new Database();
      }
      $result = $db->query("SELECT ST_asText(ST_Transform(ST_GeomFromText('$wkt',$fromSrid),$toSrid)) AS wkt;")->current();
      return $result->wkt;
    }
    else {
      return $wkt;
    }
  }

  public static function setOccurrenceCreatorByCmsUser($websiteId, $userId, $cmsUserId, $db = NULL) {
    if (!$db) {
      $db = new Database();
    }
    $db->query(<<<QRY
update occurrences as o
set created_by_id=$userId, updated_by_id=$userId, updated_on=now()
from sample_attribute_values sav
join sample_attributes sa
    on sa.id=sav.sample_attribute_id
    and sa.caption='CMS User ID'
    and sa.deleted=false
where o.sample_id = sav.sample_id
and sav.deleted=false
and o.deleted=false
and o.website_id=$websiteId
and sav.int_value=$cmsUserId
and o.created_by_id<>$userId
and o.created_by_id=1
QRY
    );
  }

  /**
   * Select verification and comment notification details since last run date.
   *
   * Runs a query to select the notification data to generate for verification
   * and comment status updates since the last run date. This allows recorders
   * to be notified of verification actions and/or comments on their records.
   * Excludes confidential records but not sensitive records, though verifiers
   * who verify or query a confidential record will be notified on
   * subsequent comments.
   */
  public static function selectVerificationAndCommentNotifications($last_run_date, $db = NULL) {
    if (!$db) {
      $db = new Database();
    }
    // Note this query excludes user 1 from the notifications (admin user) as
    // they are records which don't have a warehouse user ID. Also excludes any
    // previous notifications of this exact source for this user. ID difficulty
    // notifications only passed through for level 3 and above.
    return $db->query(<<<SQL
-- autogenerated record cleaner comments
select distinct 'A' as source_type, co.id, co.created_by_id as notify_user_id, cttl.taxon, co.date_start, co.date_end, co.date_type, snf.public_entered_sref,
        co.verified_on, oc.comment, true as auto_generated, oc.generated_by, co.record_status, co.record_substatus, co.updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        oc.generated_by as source_detail, 't' as record_owner, false as allow_confidential
into temporary records_to_notify
from cache_occurrences_functional co
join cache_samples_nonfunctional snf on snf.id=co.sample_id
join cache_taxa_taxon_lists cttl on cttl.id=co.taxa_taxon_list_id
join occurrence_comments oc on oc.occurrence_id=co.id and oc.deleted=false and oc.created_on>'$last_run_date'
  and oc.auto_generated=true
  and (coalesce(oc.generated_by, '')<>'data_cleaner_identification_difficulty' or coalesce(oc.generated_by_subtype, '') not in ('1','2'))
  and oc.record_status is null -- exclude auto-generated verifications
where co.created_by_id<>1
-- verifications (human only, no longer notify on automated verification)
union
select distinct 'V', co.id, co.created_by_id as notify_user_id, cttl.taxon, co.date_start, co.date_end, co.date_type, snf.public_entered_sref,
        co.verified_on, oc.comment, oc.auto_generated, oc.generated_by, co.record_status, co.record_substatus, co.updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        'oc_id:' || oc.id::varchar as source_detail, 't' as record_owner, false as allow_confidential
from cache_occurrences_functional co
join cache_samples_nonfunctional snf on snf.id=co.sample_id
join cache_taxa_taxon_lists cttl on cttl.id=co.taxa_taxon_list_id
left join occurrence_comments oc on oc.occurrence_id=co.id and oc.deleted=false and oc.created_on>'$last_run_date'
  and oc.record_status is not null -- verifications
  and oc.auto_generated = false -- but exclude auto-generated verifications
where co.created_by_id<>1 and co.verified_on>'$last_run_date'
-- a comment on your record
union
select distinct 'C', co.id, co.created_by_id as notify_user_id, cttl.taxon, co.date_start, co.date_end, co.date_type, snf.public_entered_sref,
        co.verified_on, oc.comment, false as auto_generated, oc.generated_by, co.record_status, co.record_substatus, co.updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        'oc_id:' || oc.id::varchar as source_detail, 't' as record_owner, false as allow_confidential
from cache_occurrences_functional co
join cache_samples_nonfunctional snf on snf.id=co.sample_id
join cache_taxa_taxon_lists cttl on cttl.id=co.taxa_taxon_list_id
join occurrence_comments oc on oc.occurrence_id=co.id and oc.deleted=false and oc.created_on>'$last_run_date'
  and oc.record_status is null and oc.auto_generated=false
left join occurrence_comments oc2 on oc2.occurrence_id=co.id and oc2.deleted=false and oc2.created_on>'$last_run_date'
  and oc2.record_status is not null and oc2.auto_generated=false
where co.created_by_id<>1 and co.created_by_id<>oc.created_by_id
and oc2.id is null -- ignore comment if accompanied by a verification from same person
-- a record you commented on then verified or a comment on a record you've previously commented on
union
select distinct 'C' as source_type, co.id, ocprev.created_by_id as notify_user_id, co.taxon, co.date_start, co.date_end, co.date_type, co.public_entered_sref,
        co.verified_on, oc.comment, oc.auto_generated, oc.generated_by, co.record_status, co.record_substatus, co.cache_updated_on as updated_on, oc.created_by_id as occurrence_comment_created_by_id,
        'oc_id:' || oc.id::varchar as source_detail, 'f' as record_owner, ocprev.record_status is not null or ocprev.query='t' as allow_confidential
from cache_occurrences co
join occurrence_comments ocprev on ocprev.occurrence_id=co.id and ocprev.deleted=false and ocprev.created_by_id<>co.created_by_id and ocprev.created_by_id<>1
join occurrence_comments oc on oc.occurrence_id=co.id and oc.deleted=false and oc.created_on>'$last_run_date' and oc.created_by_id<>ocprev.created_by_id and oc.id>ocprev.id
where co.created_by_id<>1 and oc.created_by_id<>1
-- only notify if not the commenter or record owner
and ocprev.created_by_id<>oc.created_by_id and ocprev.created_by_id<>co.created_by_id;

select rn.*, u.username
from records_to_notify rn
join occurrences o on o.id=rn.id
left join notifications n on n.linked_id=o.id
          and n.source_type=rn.source_type
          and n.source_detail=rn.source_detail
join users u on u.id=coalesce(rn.occurrence_comment_created_by_id, o.verified_by_id)
where n.id is null
and (o.confidential=false or rn.allow_confidential = true);
SQL
    )->result();
  }

  /**
   * Runs a query to select the notification data to generate for verification and comment status updates since the
   * last run date. This allows recorders to be notified of verification actions and/or comments on their records.
   */
  public static function selectPendingGroupsUsersNotifications($last_run_date, $db = NULL) {
    if (!$db) {
      $db = new Database();
    }
    // note this query excludes user 1 from the notifications (admin user) as they are records which don't
    // have a warehouse user ID.
    return $db->query(<<<SQL
select gu.id as groups_user_id, g.id as group_id,
  p.surname, p.first_name, g.title as group_title, a.user_id as notify_user_id
from groups_users gu
join users u on u.id=gu.user_id and u.deleted=false
join people p on p.id=u.person_id and p.deleted=false
join groups g on g.id=gu.group_id and g.deleted=false
join groups_users a on a.group_id=g.id and a.deleted=false and a.administrator=true
left join notifications n on n.source_type='GU' and n.linked_id=gu.id
where gu.created_on>'$last_run_date'
and gu.pending=true
and n.id is null
SQL
    )->result();
  }

  /**
   * Inserts map squares for a list of samples.
   *
   * Function to be called on postSubmit of a sample, to make sure that any
   * changed occurrences are linked to their map square entries properly.
   */
  public static function insertMapSquaresForSamples($ids, $size, $db = NULL) {
    self::insertMapSquares($ids, 's', $size, $db);
  }

  /**
   * Inserts map squares for a list of occurrences.
   *
   * Function to be called on postSubmit of an occurrence or occurrences if
   * submitted directly (i.e. not as part of a sample), to make sure that any
   * changed occurrences are linked to their map square entries properly.
   */
  public static function insertMapSquaresForOccurrences($ids, $size, $db = NULL) {
    self::insertMapSquares($ids, 'o', $size, $db);
  }

  /**
   * Generic shared code for the insertMapSquaresFor... methods.
   */
  private static function insertMapSquares($ids, $alias, $size, $db = NULL) {
    if (count($ids) > 0) {
      static $srid;
      if (!isset($srid)) {
        $srid = kohana::config('sref_notations.internal_srid');
      }
      if (!$db)
        $db = new Database();
      $idlist = implode(',', $ids);
      // Seems much faster to break this into small queries than one big left join.
      $smpInfo = $db->query(
      "SELECT DISTINCT s.id, o.website_id, s.survey_id, st_astext(coalesce(s.geom, l.centroid_geom)) as geom, o.confidential,
          GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(entered_sref_system)))))::integer, o.sensitivity_precision, s.privacy_precision, $size) as size,
          coalesce(s.entered_sref_system, l.centroid_sref_system) as entered_sref_system,
          round(st_x(st_centroid(reduce_precision(
            coalesce(s.geom, l.centroid_geom), o.confidential,
            GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(entered_sref_system)))))::integer, o.sensitivity_precision, s.privacy_precision, $size),
            s.entered_sref_system)
          ))) as x,
          round(st_y(st_centroid(reduce_precision(
            coalesce(s.geom, l.centroid_geom), o.confidential,
            GREATEST(round(sqrt(st_area(st_transform(s.geom, sref_system_to_srid(entered_sref_system)))))::integer, o.sensitivity_precision, s.privacy_precision, $size), s.entered_sref_system)
          ))) as y
        FROM samples s
        JOIN occurrences o ON o.sample_id=s.id
        LEFT JOIN locations l on l.id=s.location_id AND l.deleted=false
        WHERE $alias.id IN ($idlist)")->result_array(TRUE);
      $km = $size / 1000;
      foreach ($smpInfo as $s) {
        $existing = $db->query("SELECT id FROM map_squares WHERE x={$s->x} AND y={$s->y} AND size={$s->size}")
          ->result_array(FALSE);
        if (count($existing)===0) {
          $qry = $db->query("INSERT INTO map_squares (geom, x, y, size)
            VALUES (reduce_precision(st_geomfromtext('{$s->geom}', $srid), '{$s->confidential}', {$s->size}, '{$s->entered_sref_system}'), {$s->x}, {$s->y}, {$s->size})");
          $msqId = $qry->insert_id();
        }
        else {
          $msqId = $existing[0]['id'];
        }
        $db->query("UPDATE cache_occurrences_functional SET map_sq_{$km}km_id=$msqId " .
          "WHERE website_id={$s->website_id} AND survey_id={$s->survey_id} AND sample_id={$s->id} " .
          "AND (map_sq_{$km}km_id IS NULL OR map_sq_{$km}km_id<>$msqId)");
        $db->query("UPDATE cache_samples_functional SET map_sq_{$km}km_id=$msqId " .
          "WHERE id={$s->id} AND (map_sq_{$km}km_id IS NULL OR map_sq_{$km}km_id<>$msqId)");
      }
    }
  }

  /**
   * List fields for an entity.
   *
   * A clone of the list_fields methods provided by the Kohana database object,
   * but with caching as it involves a database hit but is called quite
   * frequently.
   *
   * @param string $entity
   *   Table or view name.
   * @param Database $db
   *   Database object if available.
   *
   * @return array
   *   Array of field definitions for the object.
   */
  public static function list_fields($entity, $db = NULL) {
    $key = "list_fields$entity";
    $cache = Cache::instance();
    $result = $cache->get($key);
    if ($result === NULL) {
      if (!$db) {
        $db = new Database();
      }
      $result = $db->query('
        SELECT column_name, column_default, is_nullable, data_type, udt_name,
          character_maximum_length, numeric_precision, numeric_precision_radix, numeric_scale
        FROM information_schema.columns
        WHERE table_name = \'' . $entity . '\'
        ORDER BY ordinal_position
      ');

      $cols = $result->result_array(TRUE);
      $result = NULL;

      foreach ($cols as $row) {
        // Make an associative array.
        $result[$row->column_name] = self::sql_type($row->data_type);

        if (!strncmp($row->column_default, 'nextval(', 8)) {
          $result[$row->column_name]['sequenced'] = TRUE;
        }

        if ($row->is_nullable === 'YES') {
          $result[$row->column_name]['null'] = TRUE;
        }
        if (strtolower(trim($row->data_type)) === 'array' && $row->udt_name === '_varchar') {
          $result[$row->column_name]['type'] = 'string';
        }
      }
      if (!isset($result)) {
        throw new Kohana_Database_Exception('database.table_not_found', $entity);
      }
      else {
        $cache->set($key, $result);
      }
    }
    return $result;
  }

  /**
   * Converts a Kohana data type name to the SQL equivalent.
   *
   * A clone of the sql_type method in the PG driver, copied here to support
   * our version of list_fields.
   *
   * @staticvar $sql_types
   *   Used to cache the sql types config per request
   *
   * @param string $str
   *   Type name.
   *
   * @return array
   *   SQL version of the type name.
   */
  protected static function sql_type($str)
  {
    static $sql_types;

    if ($sql_types === NULL) {
      // Load SQL data types.
      $sql_types = Kohana::config('sql_types');
    }

    $str = strtolower(trim($str));

    if (($open  = strpos($str, '(')) !== FALSE) {
      // Find closing bracket.
      $close = strpos($str, ')', $open) - 1;

      // Find the type without the size.
      $type = substr($str, 0, $open);
    }
    else {
      // No length.
      $type = $str;
    }

    empty($sql_types[$type]) and exit(
      'Unknown field type: ' . $type . '. ' .
      'Please report this: http://trac.kohanaphp.com/newticket'
    );

    // Fetch the field definition.
    $field = $sql_types[$type];

    switch ($field['type']) {
      case 'string':
      case 'float':
        if (isset($close)) {
          // Add the length to the field info.
          $field['length'] = substr($str, $open + 1, $close - $open);
        }
        break;

      case 'int':
        // Add unsigned value.
        $field['unsigned'] = (strpos($str, 'unsigned') !== FALSE);
        break;
    }

    return $field;
  }

  /**
   * Simple utility method to throw an exception on failure of a condition.
   *
   * @param bool $condition
   *   True or false condition to test.
   * @param string $error
   *   The error message to throw.
   *
   * @throws exception
   */
  private static function assert($condition, $error) {
    if (!$condition) {
      throw new exception($error);
    }
  }

  /**
   * Checks a taxon search integer[] parameter format.
   *
   * Checks the format of a parameter of type integer[] and converts it to a
   * comma separated list ready for insertion into the taxon search query.
   *
   * @param array $options
   * @param string $name
   */
  private static function integerListOption(&$options, $name) {
    if (!empty($options[$name])) {
      // If an array, implode into a list ready for the query.
      if (is_array($options[$name])) {
        // Stripping " from value helps tolerate JSON encoded strings
        // containing numbers.
        $options[$name] = str_replace('"', '', implode(',', $options[$name]));
      }
      else {
        $options[$name] = (string) $options[$name];
      }
      self::assert(preg_match('/^(\d+(,\d+)*|null)$/', $options[$name]),
          "taxonSearchQuery $name option with must be a list ID or an array of list IDs. " .
          var_export($options[$name], TRUE));
    }
  }

  /**
   * Checks a taxon search string[] parameter format.
   *
   * Checks the format of a parameter of type string[] and converts it to a comma separated list ready for insertion
   * into the taxon search query.
   *
   * @param array $options
   *   Options passed to taxon search.
   * @param string $name
   *   Name of the option to check.
   */
  private static function stringListOption(array &$options, $name) {
    if (!empty($options[$name])) {
      // Convert single values to arrays so can treat everything the same.
      $optionValue = is_array($options[$name]) ? $options[$name] : [$options[$name]];
      // Escape quotes in the strings and wrap in single quotes ready for SQL
      // in clause.
      $func = function ($value) {
        return $value === 'null' ? 'null' : pg_escape_literal($value);
      };
      $options[$name] = implode(',', array_map($func, $optionValue));
    }
    else {
      $options[$name] = '';
    }
  }

  /**
   * Checks that all provided boolean options are actually boolean.
   *
   * @param array $options
   * @param array $keysToCheck List of keys in the options array which should be booleans.
   */
  private static function checkBooleanOptions($options, $keysToCheck) {
    foreach ($keysToCheck as $key) {
      if (isset($options[$key])) {
        self::assert(is_bool($options[$key]),
            "taxonSearchQuery $key option must be a boolean.");
      }
    }
  }

  /**
   * Performs sanity checking on the options passed to the taxon search query.
   *
   * @param array $options
   */
  private static function taxonSearchCheckOptions(&$options) {
    // Apply default options.
    $options = array_merge(array(
        'language' => array(),
        'abbreviations' => TRUE,
        'searchAuthors' => FALSE,
        'wholeWords' => FALSE,
        'count' => FALSE
    ), $options);
    // taxon_list_id option required.
    self::assert(!empty($options['taxon_list_id']) || !empty($options['taxa_taxon_list_id']),
        'taxonSearchQuery requires a taxon_list_id or taxa_taxa_list_id option.');
    self::integerListOption($options, 'taxon_list_id');
    self::integerListOption($options, 'taxon_group_id');
    self::stringListOption($options, 'taxon_group');
    self::integerListOption($options, 'taxon_meaning_id');
    self::integerListOption($options, 'taxa_taxon_list_id');
    self::integerListOption($options, 'preferred_taxa_taxon_list_id');
    self::stringListOption($options, 'preferred_taxon');
    self::stringListOption($options, 'external_key');
    self::integerListOption($options, 'parent_id');
    self::stringListOption($options, 'language');
    self::checkBooleanOptions($options,
        ['preferred', 'commonNames', 'synonyms', 'abbreviations', 'marine_flag', 'searchAuthors', 'wholeWords']);
  }

  /**
   * Converts the input text into a parameter that can be passed into PostgreSQL's full text search.
   *
   * @param string $search
   * @param array $options
   *
   * @return string
   */
  private static function taxonSearchGetFullTextSearchTerm($search, $options) {
    $booleanTokens = array('&', '|');
    $searchWithBooleanLogic = trim(str_replace(
      array(' and ', ' or ', '*'),
      array(' & ', ' | ', ' '),
      $search
    ));
    $tokens = explode(' ', $searchWithBooleanLogic);
    foreach ($tokens as $idx => &$token) {
      if (!$options['wholeWords'] && !in_array($token, $booleanTokens)) {
        $addBracket = preg_match('/\)$/', $token);
        $token = preg_replace('/\)$/', '', $token);
        $token .= ':*' . ($addBracket ? ')' : '');
      }
      if ($idx < count($tokens) - 1 &&
        !in_array($tokens[$idx], $booleanTokens) && !in_array($tokens[$idx + 1], $booleanTokens)) {
        $token .= ' &';
      }
    }
    return implode(' ', $tokens);
  }

  /**
   * Prepares the part of the taxon search query SQL which limits the results to the context, e.g. the taxon group.
   *
   * @param array $options
   *   Options array passed to taxon search.
   *
   * @return string
   *   SQL clause to include in query WHERE.
   */
  private static function taxonSearchGetQueryContextFilter(array $options) {
    $filters = [];
    $params = [
      'taxon_list_id', 'taxon_group_id', 'taxon_group', 'taxon_meaning_id', 'taxa_taxon_list_id',
      'preferred_taxa_taxon_list_id', 'preferred_taxon', 'external_key', 'parent_id'
    ];
    foreach ($params as $param) {
      if (!empty($options[$param])) {
        if ($options[$param] === 'null') {
          $filters[] = "cts.$param is null";
        }
        else {
          $list = $options[$param];
          $filters[] = "cts.$param in ($list)";
        }
      }
    }
    if (!empty($options['min_taxon_rank_sort_order'])) {
      $filters[] = "cts.taxon_rank_sort_order >= $options[min_taxon_rank_sort_order]";
    }
    if (!empty($options['max_taxon_rank_sort_order'])) {
      $filters[] = "cts.taxon_rank_sort_order <= $options[max_taxon_rank_sort_order]";
    }
    return implode("\nAND ", $filters);
  }

  /**
   * Prepares name types filter for taxon search query.
   *
   * Prepares the part of the taxon name search query which deals with the type
   * of taxon name (language, abbreviation, preferred, commonNames and synonyms
   * parameters).
   *
   * @param array $options
   *   Options array passed to taxon search.
   *
   * @return string
   *   SQL clause to include in query WHERE.
   */
  private static function taxonSearchGetQueryNameTypesFilter(array $options) {
    $filters = [];
    if (isset($options['language'])) {
      // Handle special case common language.
      if (strpos($options['language'], "'common'") !== FALSE) {
        // Common means not lat.
        $filters[] = "cts.language_iso<>'lat'";
        // Clean up the fake 'common' language.
        $array = explode(',', $options['language']);
        unset($array['common']);
        $options['language'] = implode(',', $array);
      }
      // If any real language the filter for them as well.
      if (strlen($options['language'])) {
        $filters[] = "cts.language_iso in ($options[language])";
      }
    }
    if (isset($options['preferred'])) {
      $filters[] = 'cts.preferred=' . ($options['preferred'] ? 'true' : 'false');
    }
    if (isset($options['commonNames'])) {
      $filters[] = $options['commonNames']
          ? "(cts.language_iso<>'lat')"
          : "(cts.language_iso='lat')";
    }
    if (isset($options['synonyms'])) {
      $filters[] = $options['synonyms']
          ? "(cts.language_iso='lat' and preferred=false)"
          : "(cts.language_iso<>'lat' or preferred=true)";
    }
    if (isset($options['marine_flag'])) {
      $filters[] = 'cts.marine_flag=' . ($options['marine_flag'] ? 'true' : 'false');
    }
    // Disable 3+2 abbreviations if search val is not 5 characters, or abbreviations explicitly disabled.
    if (!empty($options['searchQuery']) && !preg_match('/^[a-z0-9]{5}$/', strtolower($options['searchQuery'])) ||
        (isset($options['abbreviations']) && $options['abbreviations'] === FALSE)) {
      $filters[] = "cts.name_type<>'A'";
    }
    return implode("\nAND ", $filters);
  }

  /**
   * Returns a construct containing several bits of information required to build the taxon search SQL.
   *
   * @param array $options
   *
   * @return array
   *   Contains the following information:
   *   * searchFilter - the SQL required to perform a searchf or the provided search value.
   *   * searchTermNoWildcards - the search term modified to exclude wildcards
   *   * headlineColumnSql - the SQL requird to generated the highlighted output version of the found term (which
   *     emboldens parts of the searched text which caused the hit to occur).
   */
  private static function taxonSearchGetQuerySearchFilterData($options) {
    if (!empty($options['searchQuery'])) {
      $searchFilters = array();
      // Cleanup.
      $search = trim(preg_replace('/\s+/', ' ', str_replace('-', '', $options['searchQuery'])));
      $fullTextSearchTerm = self::taxonSearchGetFullTextSearchTerm($search, $options);
      $searchTerm = str_replace(array(' and ', ' or ', ' & ', ' | '), '', $search);
      $searchTermNoWildcards = str_replace('*', ' ', $searchTerm);
      $searchField = 'original';
      if ($options['searchAuthors']) {
        $searchField .= " || ' ' || coalesce(authority, '')";
      }
      if (preg_match('/\*[^\s]/', strtolower($searchTerm))) {
        // Search term contains a wildcard not at the end of a word, so enable a basic text search which supports this.
        // Use term simplification to reduce misses due to punctuation, spacing, capitalisation etc.
        $likesearchterm = preg_replace('[^a-zA-Z0-9%\+\?*]', '', str_replace(array('*', ' '), '%', str_replace('ae', 'e', preg_replace('/\(.+\)/', '', strtolower($searchTerm))))) . '%';
        $searchFilters[] = "(cts.simplified=true and searchterm like '$likesearchterm')";
        $highlightRegex = '(' . preg_replace(array(
          // wildcard * at the beginning is removed so leading characters not highlighted
          '/^\*/',
          // any other * or space will be replaced by a regex wildcard to match anything
          '/[\*\s]/',
          // all other characters (i.e. not a regex wildcard) will be altered to allow optional space afterwards so the search can
          // go across word boundaries, including skipping of subgenera in brackets.
          '/([^(\.\+)])/'
        ), array(
          '',
          '.+',
          '$1( )?( \(.+\) )?'
        ), $searchTerm) . ')';
        $headlineColumnSql = "regexp_replace(original,  '$highlightRegex', E'<b>\\\\1</b>', 'i') as highlighted";
      }
      else {
        // No wildcard in a word, so we can use full text search - this must
        // match one of the indexes created
        $searchFilters[] = "(cts.simplified=false and to_tsvector('simple', quote_literal(quote_literal($searchField))) @@ to_tsquery('simple', '$fullTextSearchTerm'))";
        $headlineColumnSql = "ts_headline('simple', quote_literal(quote_literal($searchField)), to_tsquery('simple', '$fullTextSearchTerm')) as highlighted";
      }
      if ($options['abbreviations'] && preg_match('/^[a-z0-9]{5}$/', strtolower($searchTerm))) {
        // abbreviations allowed and 5 characters input, so also include search for them.
        $searchFilters[] = "(cts.name_type='A' and cts.searchterm = '$searchTerm')";
      }
      return array(
        'searchFilter' => '(' . implode(' or ', $searchFilters) . ')',
        'searchTermNoWildcards' => $searchTermNoWildcards,
        'headlineColumnSql' => $headlineColumnSql,
      );
    }
    else {
      // Listing species names, rather than searching. Only want unsimplified
      // names.
      return array(
        'searchFilter' => 'simplified=false',
        'searchTermNoWildcards' => '',
        'headlineColumnSql' => 'original',
      );
    }
  }

  /**
   * Returns the SQL for the columns list for the taxon search query.
   *
   * @param bool $isCount
   *   Set to true for a count query.
   * @param array $searchFilterData
   *
   * @return string
   */
  private static function taxonSearchGetColsListSql($isCount, $searchFilterData) {
    if ($isCount) {
      return 'count(*)';
    }
    else {
      return <<<SQL
  cts.taxa_taxon_list_id,
  cts.searchterm,
  $searchFilterData[headlineColumnSql],
  cts.original as taxon,
  cts.authority,
  cts.language_iso,
  cts.preferred_taxon,
  cts.preferred_authority,
  cts.default_common_name,
  cts.taxon_group,
  cts.preferred,
  cts.preferred_taxa_taxon_list_id,
  cts.taxon_meaning_id,
  cts.external_key,
  cts.taxon_group_id,
  cts.parent_id,
  cts.identification_difficulty,
  cts.id_diff_verification_rule_id,
  cts.taxon_rank_sort_order

SQL;
    }
  }

  /**
   * Returns the SQL for the order by section of the taxon search query.
   *
   * @param bool $isCount
   *   Set to true for a count query. Order by is not required for count
   *   queries.
   * @param array $searchFilterData
   *
   * @return string
   */
  private static function taxonSearchGetOrderBySql($isCount, $searchFilterData) {
    if ($isCount) {
      return '';
    }
    elseif (empty($searchFilterData['searchTermNoWildcards'])) {
      return <<<SQL
order by taxonomic_sort_order, original
SQL;
    }
    else {
      return <<<SQL
order by
-- abbreviation hits come first if enabled
cts.name_type='A' DESC,
-- prefer matches in correct epithet order
searchterm ilike '%' || replace('$searchFilterData[searchTermNoWildcards]', ' ', '%') || '%' DESC,
-- prefer matches with searched phrase near start of term, by discarding the characters from the search term onwards and counting the rest
length(regexp_replace(searchterm, replace('$searchFilterData[searchTermNoWildcards]', ' ', '.*') || '.*', '','i')),
-- prefer matches where the full search term is close together, by counting the characters in the area covered by the search term
case
  when searchterm ilike '%' || replace('$searchFilterData[searchTermNoWildcards]', ' ', '%') || '%'
    then length((regexp_matches(searchterm, replace('$searchFilterData[searchTermNoWildcards]', ' ', '.*'), 'i'))[1])
  else 9999 end,
cts.preferred desc,
-- finally alpha sort
taxon,
authority
SQL;
    }
  }

  /**
   * Returns the limit and offset parts of the taxon search query.
   *
   * Count queries do not set the limit or offset.
   *
   * @param array $options
   * @return string
   */
  private static function taxonSearchGetLimitOffsetSql($options) {
    if ($options['count']) {
      return '';
    } else {
      $limitOffset = array();
      if (isset($options['limit']) && preg_match('/^\d+$/', $options['limit'])) {
        $limitOffset[] = "limit $options[limit]";
      }
      if (isset($options['offset']) && preg_match('/^\d+$/', $options['offset'])) {
        $limitOffset[] = "offset $options[offset]";
      }
      return implode(' ', $limitOffset);
    }

  }

  /**
   * Prepares a query for searching taxon names.
   *
   * Optimised to use full text search where possible.
   *
   * @param array $options
   *   Options to control the search, including:
   *   * taxon_list_id - required unless filtering by a specific list of
   *     taxa_Taxon_lists_ids. ID of the taxon list or an array of list IDs
   *     to search.
   *   * searchQuery - text to search for.
   *   * taxon_group_id - ID or array of IDs of taxon groups to limit the search to.
   *   * taxon_group - Taxon group name or array of taxon group names to limit the search to, an alternative to using
   *     taxon_group_id.
   *   * taxon_meaning_id - ID or array of IDs of taxon meanings to limit the search to.
   *   * taxa_taxon_list_id - ID or array of IDs of taxa taxon list records to limit the search to.
   *   * preferred_taxa_taxon_list_id - ID or array of IDs of taxa taxon list records to limit the search to, using
   *     the preferred name's ID to filter against, therefore including synonyms and common names in the search.
   *   * preferred_taxon - preferred taxon name or array of preferred names to limit the search to (e.g. limit to a list
   *     of species names). Exact matches required.
   *   * external_key - External key or array of external keys to limit the search to (e.g. limit to a list of TVKs).
   *   * parent_id - ID of a taxa_taxon_list record limit the search to children of, e.g. a species when searching the
   *     subspecies. May be set to null to force top level only. Pass null to search top level taxa only.
   *   * language - array of name languages to include in search results. Pass a 3 character iso code for the language,
   *     e.g. "lat" for Latin names or "eng" for English names.
   *   * preferred - set to true to limit to preferred names, false to limit to non-preferred names. E.g. filter
   *     language=lat&preferred=false to find all synonyms.
   *   * commonNames - set to true to limit to common names (non-latin names) or false to exclude non-latin names.
   *   * synonyms - set to true to limit to synonyms (latin names which are not the preferred name) or false to exclude
   *     synonyms.
   *   * abbreviations - boolean, default true. Set to false to disable searching 2+3 character species name
   *     abbreviations.
   *   * marine_flag - set to true for only marine associated species, false to exclude marine-associated species.
   *   * searchAuthors - boolean, default false. Set to true to include author strings in the searched text.
   *   * wholeWords - boolean, default false. Set to true to only search whole words in the full text index, otherwise
   *     searches the start of words.
   *   * min_taxon_rank_sort_order - integer. Minimum taxon rank to include in results. Can be used to exclude higher
   *     taxa from the search results (e.g. you might only want to records of genera or higher).
   *   * max_taxon_rank_sort_order - integer. Maximum taxon rank to include in results. Can be used to exclude lower
   *     taxa from the search results (e.g. you might want to exclude sub-species from the search results).
   *   * count - set to true to return a results count query.
   *   * limit - set to limit number of records returned.
   *   * offset - set to offset the query results from the start of the dataset for paging.
   *
   * @return string
   *   SQL to run.
   *
   * @throws exception
   *   If parameters are of incorrect format.
   */
  public static function taxonSearchQuery(array $options = []) {
    self::taxonSearchCheckOptions($options);
    $searchFilterData = self::taxonSearchGetQuerySearchFilterData($options);
    $nameTypesFilter = self::taxonSearchGetQueryNameTypesFilter($options);
    // Name types filter can be empty. If not we need an extra AND.
    if (!empty($nameTypesFilter)) {
      $searchFilterData['searchFilter'] = 'AND ' . $searchFilterData['searchFilter'];
    }
    $contextFilter = self::taxonSearchGetQueryContextFilter($options);
    $cols = self::taxonSearchGetColsListSql($options['count'], $searchFilterData);
    $orderBy = self::taxonSearchGetOrderBySql($options['count'], $searchFilterData);
    $limitOffsetSql = self::taxonSearchGetLimitOffsetSql($options);
    // Build SQL query.
    $query = <<<SQL
select $cols
from cache_taxon_searchterms cts
where $nameTypesFilter
/* filter for the input search term */
$searchFilterData[searchFilter]
/* end search term */
/* Context filter */
and $contextFilter
/* End context filter */
$orderBy
$limitOffsetSql;
SQL;
    return $query;
  }

}
