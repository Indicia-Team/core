<?php

/**
 * @file
 * Data cleaner plugin functions.
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
 * Create a menu item for the list of taxon designations.
 */
function data_cleaner_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin') || $auth->has_any_website_access('admin')) {
    $menu['Admin']['Verification rules'] = 'verification_rule';
  }
  return $menu;
}

/**
 * Extend data services to support verification rules.
 *
 * Adds the verification_rule and verification_rule_metadata entities to the
 * list available via data services.
 *
 * @return array
 *   List of additional entities to expose via the data services.
 */
function data_cleaner_extend_data_services() {
  return array(
    'verification_rules' => array('readOnly', 'allow_full_access' => 1),
    'verification_rule_metadata' => array('readOnly', 'allow_full_access' => 1),
  );
}

/**
 * Returns plugin metadata.
 *
 * Identifies that the plugin uses the occdelta table to identify changes.
 *
 * @return array
 *   Metadata.
 */
function data_cleaner_metadata() {
  return array(
    'requires_occurrences_delta' => TRUE,
  );
}

/**
 * Hook into the task scheduler to run the rules against new records.
 */
function data_cleaner_scheduled_task($timestamp, $db, $endtime) {
  $rules = data_cleaner::getRules();
  data_cleaner_cleanout_old_messages($rules, $db);
  data_cleaner_run_rules($rules, $db);
  data_cleaner_update_occurrence_metadata($db, $endtime);
  data_cleaner_set_cache_fields($db);
}

/**
 * Remove messages from occurrences that are about to be rescanned.
 *
 * Loop through the rules. For each distinct module, clean up any old messages
 * for occurrences we are about to rescan.
 *
 * @param array $rules
 *   List of rule definitions.
 * @param object $db
 *   Database connection object.
 */
function data_cleaner_cleanout_old_messages(array $rules, $db) {
  $modulesDone = array();
  foreach ($rules as $rule) {
    if (!in_array($rule['plugin'], $modulesDone)) {
      // Mark delete any previous occurrence comments for this plugin for
      // taxa we are rechecking.
      $query = "update occurrence_comments oc
        set deleted=true
        from occdelta o
        where oc.occurrence_id=o.id and o.record_status not in ('I','V','R','D')
        and oc.generated_by='$rule[plugin]'";
      $db->query($query);
      $modulesDone[] = $rule['plugin'];
    }
    // Cleanup the notifications generated previously for verifications and
    // auto-checks.
    $query = "delete
      from notifications
      using occdelta o
      where source='Verifications and comments' and source_type in ('V','A')
      and linked_id = o.id
      and o.record_status not in ('I','V','R','D')";
    $db->query($query);
  }
}

/**
 * Run through the list of data cleaner rules.
 *
 * Runs the queries to generate comments in the occurrences.
 */
function data_cleaner_run_rules($rules, $db) {
  $count = 0;
  foreach ($rules as $rule) {
    $tm = microtime(TRUE);
    if (isset($rule['errorMsgField'])) {
      // Rules are able to specify a different field (e.g. from the
      // verification rule data) to provide the error message.
      $errorField = $rule['errorMsgField'];
    }
    else {
      $errorField = 'error_message';
    }
    foreach ($rule['queries'] as $query) {
      // Queries can override the error message field.
      $ruleErrorField = isset($query['errorMsgField']) ? $query['errorMsgField'] : $errorField;
      $implies_manual_check_required = isset($query['implies_manual_check_required']) && !$query['implies_manual_check_required'] ? 'false' : 'true';
      $errorMsgSuffix = isset($query['errorMsgSuffix']) ? $query['errorMsgSuffix'] : (isset($rule['errorMsgSuffix']) ? $rule['errorMsgSuffix'] : '');
      $subtypeField = empty($query['subtypeField']) ? '' : ", generated_by_subtype";
      $subtypeValue = empty($query['subtypeField']) ? '' : ", $query[subtypeField]";
      $sql = <<<SQL
insert into occurrence_comments (comment, created_by_id, created_on,
  updated_by_id, updated_on, occurrence_id, auto_generated, generated_by, implies_manual_check_required$subtypeField)
select distinct $ruleErrorField$errorMsgSuffix,
  1, now(), 1, now(), co.id, true, '$rule[plugin]', $implies_manual_check_required$subtypeValue
from occdelta co
SQL;
      if (isset($query['joins'])) {
        $sql .= "\n$query[joins]";
      }
      $sql .= "\nwhere ";
      if (isset($query['where'])) {
        $sql .= "$query[where] \nand ";
      }
      $sql .= "co.verification_checks_enabled=true\nand co.record_status not in ('I','V','R','D')";
      if (isset($query['groupBy'])) {
        $sql .= "\n$query[groupBy]";
      }
      // We now have the query ready to run which will return a list of the
      // occurrence ids that fail the check.
      try {
        $count += $db->query($sql)->count();
      }
      catch (Exception $e) {
        echo 'Query failed<br/>' . $e->getMessage() . '<br/><pre style="color: red">' . $db->last_query() . '</pre><br/>';
      }
    }
    $tm = microtime(TRUE) - $tm;
    if ($tm > 3) {
      kohana::log('alert', "Data cleaner rule $rule[testType] took $tm seconds");
    }
  }

  echo "Data cleaner generated $count messages.<br/>";
}

/**
 * Update the metadata associated occurrences so we know rules have been run.
 *
 * @param object $db
 *   Kohana database instance.
 */
function data_cleaner_update_occurrence_metadata($db, $endtime) {
  // Note we use the information from the point when we started the process,
  // in caseany changes have happened in the meanwhile which might otherwise be
  // missed.
  $query = "update occurrences o
set last_verification_check_date='$endtime'
from occdelta
where occdelta.id=o.id and occdelta.record_status not in ('I','V','R','D')";
  $db->query($query);
}

/**
 * Sets field values in the cache reporting tables.
 *
 * Update the cache_occurrences_functional.data_cleaner_result and
 * cache_occurrences_nonfunctional.data_cleaner_info fields.
 */
function data_cleaner_set_cache_fields($db) {
  if (in_array(MODPATH . 'cache_builder', Kohana::config('config.modules'))) {
    $query = <<<SQL
SELECT o.id,
  CASE WHEN occ.last_verification_check_date IS NULL THEN NULL ELSE
    COALESCE(string_agg(distinct '[' || oc.generated_by || ']{' || oc.comment || '}', ' '), 'pass')
  END AS data_cleaner_info,
  CASE WHEN occ.last_verification_check_date IS NULL THEN NULL ELSE COUNT(oc.id)=0 END AS data_cleaner_result
INTO TEMPORARY data_cleaner_results
FROM occdelta o
JOIN occurrences occ on occ.id=o.id
LEFT JOIN occurrence_comments oc ON oc.occurrence_id=o.id
         AND oc.implies_manual_check_required=true
         AND oc.deleted=false
GROUP BY o.id, occ.last_verification_check_date;

UPDATE cache_occurrences_functional o
SET data_cleaner_result = dcr.data_cleaner_result
FROM data_cleaner_results dcr
WHERE dcr.id=o.id;

UPDATE cache_occurrences_nonfunctional o
SET data_cleaner_info = dcr.data_cleaner_info
FROM data_cleaner_results dcr
WHERE dcr.id=o.id;

DROP TABLE data_cleaner_results;
SQL;
    $db->query($query);
  }
}
