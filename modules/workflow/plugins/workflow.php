<?php

/**
 * @file
 * Plugin for the workflow module.
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
 * @package Modules
 * @subpackage Workflow
 * @author Indicia Team
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/Indicia-Team/
 */

// @todo add tab to occurrences to display workflow_undo data
// @todo Build in ability to handle default?

/**
 * Implements the alter_menu hook.
 *
 * Determines the extra items to be added to the main Indicia menu.
 *
 * @return array
 *   List of menu items exposed by this plugin.
 */
function workflow_alter_menu($menu, $auth) {
  if ($auth->logged_in('CoreAdmin')) {
    $menu['Admin']['Workflow Events'] = 'workflow_event';
    $menu['Admin']['Workflow Metadata'] = 'workflow_metadata';
  }
  return $menu;
}

/**
 * Implements the extend_data_services hook.
 *
 * Determines the data entities which should be added to those available via data services.
 *
 * @return array
 *   List of database entities exposed by this plugin with configuration.
 */
function workflow_extend_data_services() {
  return array(
    'workflow_events' => array(),
    'workflow_metadata' => array()
  );
}

/**
 * Pre-record save processing hook.
 *
 * Potential problem when a record matches multiple events, and they change the same columns,
 * so we are making the assumption that each record will only fire one alert key/key_value combination
 * undo record would require more details on firing event (key and key_value) if this is changed in future
 * In following code, entity means the orm entity, e.g. 'occurrence'
 *
 * @param object $db
 *   Database connection.
 * @param string $entity
 *   Name of the database entity being saved, e.g. occurrence.
 * @param object $oldRecord
 *   Original record values in ORM object.
 * @param object $newRecord
 *   Values being saved, which may be updated by the workflow event rules.
 *
 * @return array
 *   State data to pass to the post save processing hook.
 */
function workflow_orm_pre_save_processing($db, $entity, $oldRecord, &$newRecord) {
  $config = kohana::config('workflow');
  $state = array();
  // Abort if no workflow configuration for this entity.
  if (!isset($config['entities'][$entity])) {
    return $state;
  }
  // Rewind the record if previous workflow rule changes no longer apply (e.g. after redetermination).
  workflow::applyRewindsIfRequired($db, $config['entities'][$entity], $entity, $oldRecord, $newRecord);
  // Apply any changes in the workflow_events table relevant to the record.
  $state = workflow::applyEvents($db, $config['entities'][$entity], $entity, $oldRecord, $newRecord);
  return $state;
}

/**
 * Post record save processing hook.
 *
 * Records any undo data for the workflow operations applied to the record.
 *
 * @param object $db
 *   Database connection.
 * @param string $entity
 *   Name of the database entity being saved, e.g. occurrence.
 * @param array|object $record
 *   Save data.
 * @param array $state
 *   State data returned by the pre-save hook.
 * @param int $id
 *   ID of saved record.
 *
 * @return bool
 *   Returns TRUE to imply success.
 */
function workflow_orm_post_save_processing($db, $entity, $record, array $state, $id) {
  if (empty($state)) {
    return TRUE;
  }
  // At this point we determine the id of the logged in user,
  // and use this in preference to the default id if possible.
  if (isset($_SESSION['auth_user'])) {
    $userId = $_SESSION['auth_user']->id;
  }
  else {
    global $remoteUserId;
    if (isset($remoteUserId)) {
      $userId = $remoteUserId;
    }
    else {
      $defaultUserId = Kohana::config('indicia.defaultPersonId');
      $userId = ($defaultUserId ? $defaultUserId : 1);
    }
  }
  // Insert any state undo records.
  foreach ($state as $undoDetails) {
    $db->insert('workflow_undo', array(
      'entity' => $entity,
      'entity_id' => $id,
      'event_type' => $undoDetails['event_type'],
      'created_on' => date("Ymd H:i:s"),
      'created_by_id' => $userId,
      'original_values' => json_encode($undoDetails['old_data'])
    ));
  }
  return TRUE;
}
