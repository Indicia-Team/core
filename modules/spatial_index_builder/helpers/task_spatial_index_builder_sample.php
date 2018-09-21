<?php

/**
 * @file
 * Queue worker to update cache_*_functional.location_ids on sample changes.
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
 * Queue worker to update cache_*_functional.location_ids on sample changes.
 */
class task_spatial_index_builder_sample {

  public const BATCH_SIZE = 5000;

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
    $locationTypeFilters = spatial_index_builder::getLocationTypeFilters($db);
    $qry = <<<SQL
DROP TABLE IF EXISTS smplist;
DROP TABLE IF EXISTS changed_samples;
SELECT record_id INTO temporary smplist
FROM work_queue
WHERE claimed_by='$procId'
AND entity='sample'
AND task='task_spatial_index_builder_sample';

SELECT DISTINCT s.id as sample_id, CASE WHEN count(l.id)=0 THEN NULL ELSE array_agg(l.id) END as location_ids
INTO TEMPORARY changed_samples
FROM cache_samples_functional s
LEFT JOIN locations l ON st_intersects(l.boundary_geom, s.public_geom)
  AND NOT st_touches(l.boundary_geom, s.public_geom)
  AND l.deleted=false
  $locationTypeFilters
  /* type filters, e.g. and (l.location_type_id<>#id or s.survey_id in (#surveys)) */
JOIN smplist sl on sl.record_id=s.id
GROUP BY s.id;

-- Samples - for updated samples, copy over the changes if there are any
UPDATE cache_samples_functional u
  SET location_ids=cs.location_ids
FROM changed_samples cs
WHERE cs.sample_id=u.id
AND (
  (u.location_ids is null<>cs.location_ids is null)
  OR u.location_ids <@ cs.location_ids = false OR u.location_ids @> cs.location_ids = false
);

UPDATE cache_occurrences_functional o
SET location_ids = s.location_ids
FROM cache_samples_functional s
JOIN changed_samples cs on cs.sample_id=s.id
WHERE o.sample_id=s.id AND o.survey_id=s.survey_id AND o.website_id=s.website_id
AND (o.location_ids <> s.location_ids OR o.location_ids IS NULL<>s.location_ids IS NULL);
SQL;
    $db->query($qry);
  }
}