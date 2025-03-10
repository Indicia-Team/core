<?php

/**
 * @file
 * Queue worker to update cache_* tables.
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
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @link https://github.com/indicia-team/warehouse
 */

 defined('SYSPATH') or die('No direct script access.');

/**
 * Queue worker to update cache_* tables.
 *
 * Class called when a task_cache_builder_update task encountered in the work
 * queue. Updates appropriate cache tables.
 */
class task_cache_builder_update {

  /**
   * Update limit to 1000 so not too resource hungry.
   */
  public const BATCH_SIZE = 1000;

  /**
   * Work_queue class will automatically expire the completed tasks.
   *
   * @const bool
   */
  public const SELF_CLEANUP = FALSE;

  /**
   * Perform the processing for a task batch found in the queue.
   *
   * @param object $db
   *   Database connection object.
   * @param object $taskType
   *   Object read from the database for the task batch. Contains the task
   *   name, entity, priority, created_on of the first record in the batch
   *   count (total number of queued tasks of this type).
   * @param string $procId
   *   Unique identifier of this work queue processing run. Allows filtering
   *   against the work_queue table's claimed_by field to determine which
   *   tasks to perform.
   */
  public static function process($db, $taskType, $procId) {
    $table = inflector::plural($taskType->entity);
    $needsUpdateTable = pg_escape_identifier($db->getLink(), "needs_update_$table");
    $sql = <<<SQL
CREATE TEMPORARY TABLE $needsUpdateTable AS
SELECT record_id AS id, COALESCE(params->>'deleted' = 'true', false) AS deleted
FROM work_queue
WHERE entity=? AND claimed_by=?
SQL;
    $db->query($sql, [$taskType->entity, $procId]);
    $constraintName = pg_escape_identifier($db->getLink(), "ix_nu_$table");
    $db->query("ALTER TABLE $needsUpdateTable ADD CONSTRAINT $constraintName PRIMARY KEY (id)");
    $rows = $db->query("SELECT DISTINCT id FROM $needsUpdateTable")->result();
    cache_builder::makeChangesWithOutput($db, $table, $rows->count());
    $ids = [];

    foreach ($rows as $row) {
      $ids[] = $row->id;
    }
    if ($table === 'samples') {
      postgreSQL::insertMapSquaresForSamples($ids, $db);
    }
    elseif ($table === 'occurrences') {
      postgreSQL::insertMapSquaresForOccurrences($ids, $db);
    }
  }

}
